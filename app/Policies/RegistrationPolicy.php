<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::RegistrationsView);
    }

    public function view(User $user, Registration $registration): bool
    {
        return $user->hasPermission(Permission::RegistrationsView);
    }

    public function accept(User $user, Registration $registration): bool
    {
        return $user->hasPermission(Permission::RegistrationsAccept);
    }

    public function reject(User $user, Registration $registration): bool
    {
        return $user->hasPermission(Permission::RegistrationsReject);
    }
}
