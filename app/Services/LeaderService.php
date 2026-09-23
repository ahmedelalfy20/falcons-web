<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Models\Competition;
use App\Models\Leader;
use App\Models\User;
use App\Support\LiveVersion;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;

class LeaderService
{
    public function __construct(
        private ReferralCodeGenerator $codes,
        private AuditLogger $audit,
        private ScoreService $scores,
    ) {}

    /**
     * Create a leader (and optionally a login account) for a competition.
     * Used by public leader sign-up and by admins.
     */
    public function create(Competition $competition, array $data, ?string $password = null, ?User $actor = null, ?string $status = null): Leader
    {
        $phone = PhoneNumber::normalize($data['phone']);

        return DB::transaction(function () use ($competition, $data, $password, $actor, $phone, $status) {
            if (Leader::where('competition_id', $competition->id)->where('phone_normalized', $phone)->lockForUpdate()->exists()) {
                throw BusinessRuleException::make('leader_exists');
            }

            $user = null;
            if ($password !== null) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => strtolower($data['email']),
                    'phone' => $data['phone'],
                    'password' => $password,
                    'role' => Role::Leader,
                    'is_active' => true,
                ]);
            }

            $leader = Leader::create([
                'competition_id' => $competition->id,
                'user_id' => $user?->id,
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
                'phone_normalized' => $phone,
                'email' => isset($data['email']) ? strtolower($data['email']) : null,
                'photo' => $data['photo'] ?? null,
                'unique_code' => $this->codes->code(),
                'qr_token' => $this->codes->qrToken(),
                'status' => $status = $status ?? ($competition->config('leader_auto_approve') ? 'active' : 'pending'),
                'approved_at' => $status === 'active' ? now() : null,
                'approved_by' => $status === 'active' ? $actor?->id : null,
            ]);

            if ($round = $competition->currentRound()) {
                $this->scores->recalculate($round->id, $leader->id);
            }
            $this->audit->log('leader.created', $leader, ['code' => $leader->unique_code, 'self_registered' => $actor === null, 'status' => $leader->status], $actor ?? $user);
            $this->bumpLive($leader);

            return $leader;
        });
    }

    public function update(Leader $leader, array $data, User $actor): Leader
    {
        return DB::transaction(function () use ($leader, $data, $actor) {
            $phone = PhoneNumber::normalize($data['phone']);
            $clash = Leader::where('competition_id', $leader->competition_id)->where('phone_normalized', $phone)->where('id', '!=', $leader->id)->exists();
            if ($clash) {
                throw BusinessRuleException::make('leader_exists');
            }
            $before = $leader->only(['name', 'phone', 'email', 'status']);
            $status = $data['status'] ?? $leader->status;
            $leader->fill([
                'name' => trim($data['name']),
                'phone' => trim($data['phone']),
                'phone_normalized' => $phone,
                'email' => $data['email'] ?? null,
                'status' => $status,
            ]);
            if (array_key_exists('photo', $data)) {
                $leader->photo = $data['photo'];
            }
            if ($status === 'active' && ! $leader->approved_at) {
                $leader->approved_at = now();
                $leader->approved_by = $actor->id;
            }
            $leader->save();
            $leader->user?->update(['name' => $leader->name, 'is_active' => $this->canLogIn($leader)]);
            $this->bumpLive($leader);
            $this->audit->log('leader.updated', $leader, ['before' => $before, 'after' => $leader->only(['name', 'phone', 'email', 'status'])], $actor);

            return $leader;
        });
    }

    /** Approve a pending leader request: the QR starts accepting registrations and the leader joins the live board. */
    public function approve(Leader $leader, User $actor): Leader
    {
        return DB::transaction(function () use ($leader, $actor) {
            $locked = Leader::whereKey($leader->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['pending', 'rejected'], true)) {
                throw new BusinessRuleException('invalid_transition', __('This leader has already been reviewed.'));
            }
            $locked->forceFill(['status' => 'active', 'approved_at' => now(), 'approved_by' => $actor->id])->save();
            $locked->user?->update(['is_active' => true]);
            if ($round = $locked->competition->currentRound()) {
                $this->scores->recalculate($round->id, $locked->id);
            }
            $this->audit->log('leader.approved', $locked, ['code' => $locked->unique_code], $actor);
            $this->bumpLive($locked);

            return $locked;
        });
    }

    public function reject(Leader $leader, User $actor, ?string $note = null): Leader
    {
        return DB::transaction(function () use ($leader, $actor, $note) {
            $locked = Leader::whereKey($leader->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw new BusinessRuleException('invalid_transition', __('Only requests awaiting approval can be rejected.'));
            }
            $locked->forceFill(['status' => 'rejected', 'approved_at' => null, 'approved_by' => $actor->id])->save();
            $this->audit->log('leader.rejected', $locked, array_filter(['code' => $locked->unique_code, 'note' => $note]), $actor);
            $this->bumpLive($locked);

            return $locked;
        });
    }

    /** Replace a leader's profile photo (path already stored by ImageOptimizer). */
    public function updatePhoto(Leader $leader, string $path, User $actor): ?string
    {
        $old = $leader->photo;
        $leader->forceFill(['photo' => $path])->save();
        $this->audit->log('leader.photo_updated', $leader, [], $actor);
        $this->bumpLive($leader);

        return $old;
    }

    /** Pending and rejected leaders can still sign in to see their request status; suspended ones cannot. */
    private function canLogIn(Leader $leader): bool
    {
        return $leader->status !== 'suspended';
    }

    private function bumpLive(Leader $leader): void
    {
        if ($round = $leader->competition?->currentRound()) {
            LiveVersion::bump('round.'.$round->id);
        }
        LiveVersion::bump('admin');
    }

    public function delete(Leader $leader, User $actor): void
    {
        DB::transaction(function () use ($leader, $actor) {
            if ($leader->registrations()->exists()) {
                throw new BusinessRuleException('invalid_transition', __('This leader has registrations and cannot be deleted. Suspend the leader instead to keep results intact.'));
            }
            $this->audit->log('leader.deleted', $leader, ['name' => $leader->name, 'code' => $leader->unique_code], $actor);
            $user = $leader->user;
            $leader->scores()->delete();
            $leader->delete();
            $user?->delete();
        });
    }
}
