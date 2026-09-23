<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Monotonic "something changed" counters used by the polling endpoints so
 * clients can cheaply detect whether they need to re-render.
 */
class LiveVersion
{
    public static function get(string $channel): int
    {
        return (int) Cache::get('live.'.$channel, 1);
    }

    public static function bump(string ...$channels): void
    {
        foreach ($channels as $channel) {
            $key = 'live.'.$channel;
            if (! Cache::has($key)) {
                Cache::forever($key, 1);
            }
            Cache::increment($key);
        }
    }
}
