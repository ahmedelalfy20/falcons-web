<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Falcons app store links (used by the "Register now" page). */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'app')->first();
        $app = $row ? (json_decode($row->value, true) ?: []) : [];
        $app['play_url'] = ($app['play_url'] ?? null) ?: 'https://play.google.com/store/apps/details?id=com.falcons.lms';
        $app['appstore_url'] = ($app['appstore_url'] ?? null) ?: 'https://apps.apple.com/eg/app/falcons-edu/id6760587004';
        DB::table('settings')->updateOrInsert(['key' => 'app'], ['value' => json_encode($app), 'updated_at' => now()]);
        Cache::forget('settings.all');
    }

    public function down(): void {}
};
