<?php

namespace App\Services;

use App\Models\Gift;
use App\Models\GiftClaim;
use App\Models\Leader;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gift ladder. A leader's lifetime points are never spent: every gift whose
 * target is at or below the total is unlocked, and the next gift shows how
 * many more points are needed.
 */
class GiftService
{
    public function __construct(private ScoreService $scores, private AuditLogger $audit) {}

    /** @return Collection<int, Gift> */
    public function ladder(): Collection
    {
        return Gift::ladder()->with('options')->get()->filter(fn (Gift $g) => $g->options->isNotEmpty())->values();
    }

    /**
     * Everything the leader dashboard needs: total points, each gift with its
     * state, and progress toward the next locked gift.
     */
    public function progressFor(Leader $leader): array
    {
        $total = $this->scores->totalPoints($leader);
        $claims = $leader->giftClaims()->get()->keyBy('gift_id');
        $gifts = $this->ladder();

        $previous = 0;
        $next = null;
        foreach ($gifts as $gift) {
            $gift->unlocked = $total >= $gift->points_required;
            $gift->claim = $claims->get($gift->id);
            if (! $gift->unlocked && ! $next) {
                $next = $gift;
            }
            if ($gift->unlocked) {
                $previous = $gift->points_required;
            }
        }

        $span = $next ? max(1, $next->points_required - $previous) : 1;
        $percent = $next ? (int) floor(min(100, max(0, ($total - $previous) / $span * 100))) : 100;

        return [
            'total' => $total,
            'gifts' => $gifts,
            'next' => $next,
            'remaining' => $next ? $next->points_required - $total : 0,
            'percent' => $percent,
            'unlockedCount' => $gifts->where('unlocked', true)->count(),
        ];
    }

    /** Record (or change) the leader's pick for an unlocked gift. */
    public function choose(Leader $leader, Gift $gift, ?int $optionId): GiftClaim
    {
        $gift->load('options');

        if (! $leader->isActive()) {
            throw ValidationException::withMessages(['gift' => __('Only active leaders can claim gifts.')]);
        }
        if (! $gift->is_active || $gift->options->isEmpty()) {
            throw ValidationException::withMessages(['gift' => __('This gift is no longer available.')]);
        }
        if ($this->scores->totalPoints($leader) < $gift->points_required) {
            throw ValidationException::withMessages(['gift' => __('You need :n points to unlock this gift.', ['n' => $gift->points_required])]);
        }

        $option = $gift->hasChoice()
            ? $gift->options->firstWhere('id', $optionId)
            : $gift->options->first();
        if (! $option) {
            throw ValidationException::withMessages(['gift' => __('Please choose one of the gift options.')]);
        }

        return DB::transaction(function () use ($leader, $gift, $option) {
            $claim = GiftClaim::where('leader_id', $leader->id)->where('gift_id', $gift->id)->lockForUpdate()->first();
            if ($claim?->isDelivered()) {
                throw ValidationException::withMessages(['gift' => __('This gift has already been delivered.')]);
            }
            $claim ??= new GiftClaim(['leader_id' => $leader->id, 'gift_id' => $gift->id]);
            $claim->fill(['gift_option_id' => $option->id, 'status' => 'requested'])->save();
            $this->audit->log('gift.claimed', $claim, ['leader_id' => $leader->id, 'gift' => $gift->name, 'option' => $option->label]);

            return $claim;
        });
    }

    public function markDelivered(GiftClaim $claim, User $by, bool $delivered = true): void
    {
        $claim->update($delivered
            ? ['status' => 'delivered', 'delivered_at' => now(), 'delivered_by' => $by->id]
            : ['status' => 'requested', 'delivered_at' => null, 'delivered_by' => null]);
        $this->audit->log($delivered ? 'gift.delivered' : 'gift.delivery_undone', $claim, ['leader_id' => $claim->leader_id, 'gift_id' => $claim->gift_id]);
    }
}
