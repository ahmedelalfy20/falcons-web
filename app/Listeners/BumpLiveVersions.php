<?php

namespace App\Listeners;

use App\Events\RegistrationReviewed;
use App\Events\RegistrationSubmitted;
use App\Events\RoundStateChanged;
use App\Support\LiveVersion;

/**
 * Real-time strategy: lightweight polling. Every state change bumps a version
 * counter; clients poll a tiny endpoint and only re-render when it changes.
 * No websocket infrastructure is required.
 */
class BumpLiveVersions
{
    public function handleRound(RoundStateChanged $event): void
    {
        LiveVersion::bump('round.'.$event->round->id, 'admin');
    }

    public function handleSubmitted(RegistrationSubmitted $event): void
    {
        LiveVersion::bump('round.'.$event->registration->round_id, 'admin', 'leader.'.$event->registration->leader_id);
    }

    public function handleReviewed(RegistrationReviewed $event): void
    {
        LiveVersion::bump(
            'round.'.$event->registration->round_id,
            'admin',
            'leader.'.$event->registration->leader_id,
            'registration.'.$event->registration->id,
        );
    }
}
