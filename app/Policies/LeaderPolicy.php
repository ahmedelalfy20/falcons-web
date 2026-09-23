<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Leader;
use App\Models\User;

class LeaderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::LeadersView);
    }

    public function view(User $user, Leader $leader): bool
    {
        return $user->hasPermission(Permission::LeadersView) || $leader->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::LeadersManage);
    }

    public function update(User $user, Leader $leader): bool
    {
        return $user->hasPermission(Permission::LeadersManage);
    }

    /** Approve or reject a leader's request to join the competition. */
    public function review(User $user, Leader $leader): bool
    {
        return $user->hasPermission(Permission::LeadersManage);
    }

    public function delete(User $user, Leader $leader): bool
    {
        return false; // super admin only
    }
}
