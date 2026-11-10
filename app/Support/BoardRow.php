<?php

namespace App\Support;

/** Public leaderboard row (no ids exposed; `me` marks the signed-in leader). */
class BoardRow
{
    public static function make(object $r, ?int $meId = null): array
    {
        return [
            'rank' => (int) $r->rank,
            'name' => $r->name,
            'team' => $r->team ?? null,
            'accepted' => (int) $r->accepted,
            'score' => (int) $r->score,
            'photo' => $r->photoUrl(),
            'initials' => $r->initials(),
            'me' => $meId !== null && (int) $r->id === $meId,
        ];
    }

    /** Top rows plus the signed-in leader's own row when they are outside the top. */
    public static function board(\Illuminate\Support\Collection $all, int $limit, ?int $meId = null): array
    {
        $rows = $all->take($limit)->map(fn ($r) => self::make($r, $meId))->values()->all();
        $mine = $meId ? $all->firstWhere('id', $meId) : null;

        return ['rows' => $rows, 'me' => $mine && $mine->rank > $limit ? self::make($mine, $meId) : null];
    }
}
