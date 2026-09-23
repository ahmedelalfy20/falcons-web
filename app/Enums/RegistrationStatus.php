<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    /** PENDING → ACCEPTED or PENDING → REJECTED. Decisions are final. */
    public function canTransitionTo(self $to): bool
    {
        return $this === self::Pending && $to !== self::Pending;
    }

    public function label(): string
    {
        return __(ucfirst($this->value));
    }
}
