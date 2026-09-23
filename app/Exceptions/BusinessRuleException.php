<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A user-safe domain error. `reason` is a stable machine key (used by views
 * and tests); the message is already translated and safe to display.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }

    public static function make(string $reason, int $status = 422): self
    {
        $messages = [
            'invalid_code' => __('This referral code is not valid. Please check the code or scan the QR again.'),
            'leader_inactive' => __('This leader is not currently accepting registrations.'),
            'competition_inactive' => __('There is no active competition right now.'),
            'no_round' => __('The competition has not started yet.'),
            'registration_closed' => __('Registration is currently closed.'),
            'competition_finished' => __('This round has finished. Registration is closed.'),
            'duplicate' => __('You are already registered in this competition. Each participant can only register once.'),
            'invalid_transition' => __('This action is not allowed in the current state.'),
            'already_reviewed' => __('This registration has already been reviewed.'),
            'leader_exists' => __('A leader with this phone number is already registered.'),
            'leader_registration_disabled' => __('Leader registration is currently closed.'),
        ];

        return new self($reason, $messages[$reason] ?? __('Something went wrong. Please try again.'), $status);
    }
}
