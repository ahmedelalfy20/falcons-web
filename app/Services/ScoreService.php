<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Leader;
use App\Models\LeaderScore;
use App\Models\Registration;
use App\Models\Round;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Scores are ALWAYS derived from registrations:
 *   score = COUNT(accepted registrations for the leader in the round)
 * leader_scores is only a cache; recalculate() rebuilds a row from source data
 * and is invoked inside the same transaction as every status change.
 */
class ScoreService
{
    public function recalculate(int $roundId, int $leaderId): LeaderScore
    {
        $counts = Registration::query()
            ->where('round_id', $roundId)
            ->where('leader_id', $leaderId)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        // Tie-breaker: when did the leader's latest counted participant submit?
        $reachedAt = Registration::query()
            ->where('round_id', $roundId)
            ->where('leader_id', $leaderId)
            ->where('status', RegistrationStatus::Accepted->value)
            ->max('created_at');

        return LeaderScore::updateOrCreate(
            ['round_id' => $roundId, 'leader_id' => $leaderId],
            [
                'accepted_count' => (int) ($counts[RegistrationStatus::Accepted->value] ?? 0),
                'pending_count' => (int) ($counts[RegistrationStatus::Pending->value] ?? 0),
                'rejected_count' => (int) ($counts[RegistrationStatus::Rejected->value] ?? 0),
                'score_reached_at' => $reachedAt,
            ],
        );
    }

    /** Rebuild every cached score for a round from source data. */
    public function recalculateRound(Round $round): int
    {
        return DB::transaction(function () use ($round) {
            $leaderIds = Leader::where('competition_id', $round->competition_id)->pluck('id')
                ->merge(Registration::where('round_id', $round->id)->distinct()->pluck('leader_id'))
                ->unique();
            foreach ($leaderIds as $id) {
                $this->recalculate($round->id, $id);
            }

            return $leaderIds->count();
        });
    }

    /**
     * Ranked leaderboard for a round. Ranking (consistent everywhere):
     *   1. accepted registrations DESC
     *   2. score_reached_at ASC  (reached the score earlier wins)
     *   3. leader id ASC         (earlier sign-up, final deterministic fallback)
     */
    public function leaderboard(Round $round, ?int $limit = null, bool $onlyActive = false): Collection
    {
        $query = Leader::query()
            ->where('leaders.competition_id', $round->competition_id)
            ->leftJoin('leader_scores as s', function ($j) use ($round) {
                $j->on('s.leader_id', '=', 'leaders.id')->where('s.round_id', '=', $round->id);
            })
            ->select('leaders.id', 'leaders.name', 'leaders.unique_code', 'leaders.status', 'leaders.photo')
            ->selectRaw('COALESCE(s.accepted_count, 0) as accepted')
            ->selectRaw('COALESCE(s.pending_count, 0) as pending')
            ->selectRaw('COALESCE(s.rejected_count, 0) as rejected')
            ->selectRaw('s.score_reached_at as reached_at')
            ->orderByDesc('accepted')
            ->orderByRaw('CASE WHEN s.score_reached_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('s.score_reached_at')
            ->orderBy('leaders.id');

        if ($onlyActive) {
            $query->where('leaders.status', 'active');
        } else {
            // Leaders awaiting approval (or rejected) are not competitors yet.
            $query->whereNotIn('leaders.status', ['pending', 'rejected']);
        }

        $rows = $query->get();
        $rank = 0;

        $ranked = $rows->map(function ($row) use (&$rank) {
            $row->rank = ++$rank;
            $row->score = (int) $row->accepted;

            return $row;
        });

        return $limit ? $ranked->take($limit)->values() : $ranked;
    }

    public function rankOf(Round $round, int $leaderId): ?object
    {
        return $this->leaderboard($round)->firstWhere('id', $leaderId);
    }
}
