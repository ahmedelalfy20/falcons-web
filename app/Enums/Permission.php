<?php

namespace App\Enums;

/**
 * Permissions that can be granted to Admin/Reviewer accounts.
 * Super Admins implicitly hold every permission (see AuthServiceProvider Gate::before).
 * Critical abilities (manage admins, settings, starting rounds, resetting) are
 * intentionally NOT grantable to reviewers — they are super-admin only.
 */
enum Permission: string
{
    case RegistrationsView = 'registrations.view';
    case RegistrationsAccept = 'registrations.accept';
    case RegistrationsReject = 'registrations.reject';
    case LeadersView = 'leaders.view';
    case LeadersManage = 'leaders.manage';
    case LeaderboardView = 'leaderboard.view';
    case TimerControl = 'timer.control';
    case ContentManage = 'content.manage';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationsView => __('View & search registrations'),
            self::RegistrationsAccept => __('Accept registrations'),
            self::RegistrationsReject => __('Reject registrations'),
            self::LeadersView => __('View leaders'),
            self::LeadersManage => __('Create & edit leaders'),
            self::LeaderboardView => __('View leaderboard'),
            self::TimerControl => __('Pause / resume / adjust the timer'),
            self::ContentManage => __('Edit website content'),
        };
    }

    /** Sensible default set for a new reviewer. */
    public static function reviewerDefaults(): array
    {
        return [
            self::RegistrationsView->value,
            self::RegistrationsAccept->value,
            self::RegistrationsReject->value,
            self::LeadersView->value,
            self::LeaderboardView->value,
        ];
    }
}
