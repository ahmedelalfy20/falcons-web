<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Enums\RoundStatus;
use App\Events\RegistrationReviewed;
use App\Events\RegistrationSubmitted;
use App\Exceptions\BusinessRuleException;
use App\Models\Competition;
use App\Models\Leader;
use App\Models\Registration;
use App\Models\Round;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    public function __construct(
        private RoundService $rounds,
        private ScoreService $scores,
        private AuditLogger $audit,
    ) {}

    /**
     * Resolve a referral code into [leader, competition, round] after checking
     * every server-side precondition. Throws BusinessRuleException otherwise.
     *
     * @return array{0: Leader, 1: Competition, 2: Round}
     */
    public function resolveContext(?string $code): array
    {
        $code = \App\Services\ReferralCodeGenerator::normalizeInput($code);
        $leader = $code !== '' ? Leader::with('competition')->where('unique_code', $code)->first() : null;
        if (! $leader) {
            throw BusinessRuleException::make('invalid_code', 404);
        }
        $competition = $leader->competition;
        if (! $competition || ! $competition->isActive()) {
            throw BusinessRuleException::make('competition_inactive');
        }
        if ($leader->isPending()) {
            throw new BusinessRuleException('leader_inactive', __('This leader is awaiting approval from the Falcons team. Registrations open as soon as the leader is approved.'));
        }
        if (! $leader->isActive()) {
            throw BusinessRuleException::make('leader_inactive');
        }
        $round = $this->rounds->syncExpired($competition->currentRound());
        if (! $round) {
            throw BusinessRuleException::make('no_round');
        }
        // The leader belongs to this competition, and the round must belong to it too.
        if ($round->competition_id !== $leader->competition_id) {
            throw BusinessRuleException::make('invalid_code');
        }
        if ($round->status === RoundStatus::Finished) {
            throw BusinessRuleException::make('competition_finished');
        }
        if (! $round->acceptsRegistrations()) {
            throw BusinessRuleException::make('registration_closed');
        }

        return [$leader, $competition, $round];
    }

    /**
     * Create a PENDING registration. Never awards points.
     *
     * @param  array{full_name:string, phone:string, email?:?string, city?:?string, notes?:?string, transfer_path?:?string}  $data
     */
    public function submit(string $code, array $data): Registration
    {
        [$leader, $competition] = $this->resolveContext($code);

        $phone = PhoneNumber::normalize($data['phone']);
        $email = filled($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null;

        try {
            $registration = DB::transaction(function () use ($leader, $competition, $data, $phone, $email) {
                // Re-read the round under lock so a concurrent pause/finish cannot slip through.
                $round = Round::whereKey($competition->currentRound()->id)->lockForUpdate()->first();
                if ($round->hasExpired()) {
                    throw BusinessRuleException::make('competition_finished');
                }
                if (! $round->acceptsRegistrations()) {
                    throw BusinessRuleException::make($round->status === RoundStatus::Finished ? 'competition_finished' : 'registration_closed');
                }

                $this->assertNotDuplicate($competition, $round, $phone, $email);

                $registration = Registration::create([
                    'competition_id' => $competition->id,
                    'round_id' => $round->id,
                    'leader_id' => $leader->id,
                    'full_name' => trim($data['full_name']),
                    'phone' => trim($data['phone']),
                    'phone_normalized' => $phone,
                    'email' => $email,
                    'email_normalized' => $email,
                    'city' => $data['city'] ?? null,
                    'team' => $data['team'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'transfer_path' => $data['transfer_path'] ?? null,
                    'status' => RegistrationStatus::Pending,
                    'public_token' => Str::random(40),
                ]);
                $this->scores->recalculate($round->id, $leader->id); // pending count only

                return $registration;
            });
        } catch (QueryException $e) {
            // Unique index hit by a concurrent duplicate submission.
            if ($this->isUniqueViolation($e)) {
                throw BusinessRuleException::make('duplicate');
            }
            throw $e;
        }

        event(new RegistrationSubmitted($registration));

        return $registration;
    }

    public function accept(Registration $registration, User $reviewer, ?string $note = null): Registration
    {
        return $this->review($registration, RegistrationStatus::Accepted, $reviewer, $note);
    }

    public function reject(Registration $registration, User $reviewer, ?string $note = null): Registration
    {
        return $this->review($registration, RegistrationStatus::Rejected, $reviewer, $note);
    }

    /**
     * Atomic review: verify PENDING → set status → recalculate score → audit log.
     * Any failure rolls back everything.
     */
    private function review(Registration $registration, RegistrationStatus $to, User $reviewer, ?string $note): Registration
    {
        $updated = DB::transaction(function () use ($registration, $to, $reviewer, $note) {
            $locked = Registration::whereKey($registration->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($to)) {
                throw BusinessRuleException::make('already_reviewed', 409);
            }

            $locked->forceFill([
                'status' => $to,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'review_note' => $note,
            ])->save();

            $score = $this->scores->recalculate($locked->round_id, $locked->leader_id);

            $this->audit->log(
                $to === RegistrationStatus::Accepted ? 'registration.accepted' : 'registration.rejected',
                $locked,
                ['leader_id' => $locked->leader_id, 'round_id' => $locked->round_id, 'score' => $score->accepted_count, 'note' => $note],
                $reviewer,
            );

            return $locked;
        });

        event(new RegistrationReviewed($updated));

        return $updated;
    }

    private function assertNotDuplicate(Competition $competition, Round $round, string $phone, ?string $email): void
    {
        $scope = $competition->config('duplicate_scope') === 'round'
            ? ['round_id' => $round->id]
            : ['competition_id' => $competition->id];

        $exists = Registration::query()
            ->where($scope)
            // Rejected registrations in earlier rounds don't block a new attempt; anything in this round does.
            ->where(function ($q) use ($round) {
                $q->where('round_id', $round->id)->orWhere('status', '!=', RegistrationStatus::Rejected->value);
            })
            ->where(function ($q) use ($phone, $email) {
                $q->where('phone_normalized', $phone);
                if ($email) {
                    $q->orWhere('email_normalized', $email);
                }
            })
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw BusinessRuleException::make('duplicate');
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $code = (string) ($e->errorInfo[0] ?? $e->getCode());

        return in_array($code, ['23000', '23505'], true) || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
