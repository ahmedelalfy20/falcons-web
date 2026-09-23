<?php

namespace App\Services;

use App\Enums\RoundStatus;
use App\Events\RoundStateChanged;
use App\Exceptions\BusinessRuleException;
use App\Models\Competition;
use App\Models\Round;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Server-authoritative round timer and state machine.
 *
 * Timing state:
 *   running  → ends_at is the source of truth (remaining = ends_at - now)
 *   paused   → remaining_seconds is the source of truth (ends_at = null)
 *   draft/ready → duration_seconds is the planned length
 * The browser only ever displays what the server reports.
 */
class RoundService
{
    public function __construct(private AuditLogger $audit, private ScoreService $scores) {}

    public function create(Competition $competition, array $attributes, ?User $actor = null): Round
    {
        return DB::transaction(function () use ($competition, $attributes, $actor) {
            // Serialise round-number allocation per competition.
            Competition::whereKey($competition->id)->lockForUpdate()->first();
            $number = (int) Round::where('competition_id', $competition->id)->max('number') + 1;

            $round = Round::create([
                'competition_id' => $competition->id,
                'number' => $number,
                'name' => $attributes['name'] ?? null,
                'status' => RoundStatus::Ready,
                'duration_seconds' => max(60, (int) ($attributes['duration_seconds'] ?? $competition->config('default_round_minutes') * 60)),
                'registration_enabled' => (bool) ($attributes['registration_enabled'] ?? true),
            ]);
            $this->scores->recalculateRound($round);
            $this->audit->log('round.created', $round, ['number' => $number, 'duration_seconds' => $round->duration_seconds], $actor);
            $this->changed($round);

            return $round;
        });
    }

    public function start(Round $round, ?User $actor = null): Round
    {
        return $this->mutate($round, function (Round $r) use ($actor) {
            $this->assertCanMove($r, RoundStatus::Running, [RoundStatus::Ready]);
            // Only one live round per competition.
            $live = Round::where('competition_id', $r->competition_id)->where('id', '!=', $r->id)
                ->whereIn('status', [RoundStatus::Running->value, RoundStatus::Paused->value])->exists();
            if ($live) {
                throw new BusinessRuleException('invalid_transition', __('Another round is already running. Finish it first.'));
            }
            $now = now();
            $r->forceFill([
                'status' => RoundStatus::Running,
                'started_at' => $now,
                'ends_at' => $now->copy()->addSeconds($r->duration_seconds),
                'paused_at' => null,
                'remaining_seconds' => null,
            ])->save();
            $this->audit->log('round.started', $r, ['ends_at' => $r->ends_at->toIso8601String()], $actor);
        });
    }

    public function pause(Round $round, ?User $actor = null): Round
    {
        return $this->mutate($round, function (Round $r) use ($actor) {
            $this->assertCanMove($r, RoundStatus::Paused);
            $remaining = $r->remainingSeconds();
            $r->forceFill([
                'status' => RoundStatus::Paused,
                'remaining_seconds' => $remaining,
                'paused_at' => now(),
                'ends_at' => null,
            ])->save();
            $this->audit->log('round.paused', $r, ['remaining_seconds' => $remaining], $actor);
        });
    }

    public function resume(Round $round, ?User $actor = null): Round
    {
        return $this->mutate($round, function (Round $r) use ($actor) {
            if ($r->status !== RoundStatus::Paused) {
                throw BusinessRuleException::make('invalid_transition');
            }
            $remaining = (int) $r->remaining_seconds;
            if ($remaining <= 0) {
                $this->doFinish($r, $actor, false);

                return;
            }
            $r->forceFill([
                'status' => RoundStatus::Running,
                'ends_at' => now()->addSeconds($remaining),
                'remaining_seconds' => null,
                'paused_at' => null,
            ])->save();
            $this->audit->log('round.resumed', $r, ['remaining_seconds' => $remaining], $actor);
        });
    }

    /** Add (positive) or remove (negative) time. */
    public function adjustTime(Round $round, int $seconds, ?User $actor = null): Round
    {
        if ($seconds === 0) {
            return $round;
        }

        return $this->mutate($round, function (Round $r) use ($seconds, $actor) {
            $before = $r->remainingSeconds();
            switch ($r->status) {
                case RoundStatus::Running:
                    $r->ends_at = $r->ends_at->copy()->addSeconds($seconds);
                    break;
                case RoundStatus::Paused:
                    $r->remaining_seconds = max(0, (int) $r->remaining_seconds + $seconds);
                    break;
                case RoundStatus::Draft:
                case RoundStatus::Ready:
                    $r->duration_seconds = max(60, (int) $r->duration_seconds + $seconds);
                    break;
                default:
                    throw BusinessRuleException::make('invalid_transition');
            }
            $r->save();
            $this->audit->log($seconds > 0 ? 'round.time_added' : 'round.time_removed', $r, [
                'seconds' => abs($seconds),
                'remaining_before' => $before,
                'remaining_after' => $r->remainingSeconds(),
            ], $actor);

            // Removing time can end the round immediately.
            if ($r->status === RoundStatus::Running && $r->ends_at->lte(now())) {
                $this->doFinish($r, $actor, false);
            }
        });
    }

    public function finish(Round $round, ?User $actor = null): Round
    {
        return $this->mutate($round, function (Round $r) use ($actor) {
            $this->assertCanMove($r, RoundStatus::Finished);
            $this->doFinish($r, $actor, false);
        });
    }

    public function setRegistration(Round $round, bool $open, ?User $actor = null): Round
    {
        return $this->mutate($round, function (Round $r) use ($open, $actor) {
            if ($r->status === RoundStatus::Finished && $open) {
                throw BusinessRuleException::make('competition_finished');
            }
            if ($r->registration_enabled === $open) {
                return;
            }
            $r->registration_enabled = $open;
            $r->save();
            $this->audit->log($open ? 'round.registration_opened' : 'round.registration_closed', $r, [], $actor);
        });
    }

    /** Finish the live round (if any) and create the next one. Previous results are kept. */
    public function startNewRound(Competition $competition, int $durationSeconds, bool $startNow, ?User $actor = null): Round
    {
        return DB::transaction(function () use ($competition, $durationSeconds, $startNow, $actor) {
            $live = Round::where('competition_id', $competition->id)
                ->whereIn('status', [RoundStatus::Running->value, RoundStatus::Paused->value, RoundStatus::Ready->value, RoundStatus::Draft->value])
                ->get();
            foreach ($live as $round) {
                if ($round->status->isLive()) {
                    $this->finish($round, $actor);
                } else {
                    // Unstarted rounds are superseded; mark them finished so they don't linger.
                    $this->mutate($round, fn (Round $r) => $this->doFinish($r, $actor, false));
                }
            }
            $round = $this->create($competition, ['duration_seconds' => $durationSeconds], $actor);

            return $startNow ? $this->start($round, $actor) : $round;
        });
    }

    /**
     * Lazily finish an expired running round. Safe to call on every request;
     * the scheduled `competition:tick` command also calls it every minute.
     */
    public function syncExpired(?Round $round): ?Round
    {
        if (! $round || ! $round->hasExpired()) {
            return $round;
        }

        return DB::transaction(function () use ($round) {
            $locked = Round::whereKey($round->id)->lockForUpdate()->first();
            if ($locked->hasExpired()) {
                $this->doFinish($locked, null, true);
                $this->changed($locked);
            }

            return $locked;
        });
    }

    private function doFinish(Round $r, ?User $actor, bool $automatic): void
    {
        $r->forceFill([
            'status' => RoundStatus::Finished,
            'finished_at' => $automatic && $r->ends_at ? $r->ends_at : now(),
            'remaining_seconds' => 0,
            'paused_at' => null,
            'registration_enabled' => false,
        ])->save();
        $this->audit->log($automatic ? 'round.auto_finished' : 'round.finished', $r, [], $automatic ? null : $actor);
    }

    private function assertCanMove(Round $r, RoundStatus $to, ?array $from = null): void
    {
        if (! $r->status->canTransitionTo($to) || ($from && ! in_array($r->status, $from, true))) {
            throw BusinessRuleException::make('invalid_transition');
        }
    }

    /** Lock the row, auto-finish if expired, apply the change, broadcast. */
    private function mutate(Round $round, Closure $callback): Round
    {
        $result = DB::transaction(function () use ($round, $callback) {
            $locked = Round::whereKey($round->id)->lockForUpdate()->firstOrFail();
            if ($locked->hasExpired()) {
                $this->doFinish($locked, null, true);
            }
            $callback($locked);

            return $locked;
        });
        $this->changed($result);

        return $result->refresh();
    }

    private function changed(Round $round): void
    {
        event(new RoundStateChanged($round));
    }
}
