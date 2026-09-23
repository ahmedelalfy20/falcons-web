<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Round;
use App\Models\User;

/**
 * Super admins pass via Gate::before. Reviewers may only nudge a live timer
 * (pause / resume / add / remove time) and only when granted timer.control.
 * Starting, finishing, new rounds and registration open/close are super-admin only.
 */
class RoundPolicy
{
    public function view(User $user, Round $round): bool
    {
        return $user->isStaff();
    }

    public function adjustTimer(User $user, Round $round): bool
    {
        return $user->hasPermission(Permission::TimerControl);
    }

    public function start(User $user, Round $round): bool
    {
        return false;
    }

    public function finish(User $user, Round $round): bool
    {
        return false;
    }

    public function toggleRegistration(User $user, Round $round): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }
}
