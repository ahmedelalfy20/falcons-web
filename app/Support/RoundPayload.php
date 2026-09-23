<?php

namespace App\Support;

use App\Models\Round;

/** Serialises the server-authoritative timer state for clients. */
class RoundPayload
{
    public static function make(Round $round): array
    {
        return [
            'id' => $round->id,
            'number' => $round->number,
            'name' => $round->displayName(),
            'status' => $round->status->value,
            'status_label' => $round->status->label(),
            'remaining_seconds' => $round->remainingSeconds(),
            'registration_open' => $round->acceptsRegistrations(),
            'registration_enabled' => (bool) $round->registration_enabled,
            'ends_at' => $round->ends_at?->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ];
    }
}
