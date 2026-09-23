<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Leader = 'leader';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super Admin'),
            self::Admin => __('Admin / Reviewer'),
            self::Leader => __('Leader'),
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Leader;
    }
}
