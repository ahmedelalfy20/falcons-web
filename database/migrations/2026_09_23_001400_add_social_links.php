<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Instagram + Facebook links for the footer (kept if an admin already set them). */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'social')->first();
        $social = $row ? (json_decode($row->value, true) ?: []) : [];
        $social['telegram'] = ($social['telegram'] ?? null) ?: 'https://t.me/FALCONSORGANIZATIONN';
        $social['instagram'] = ($social['instagram'] ?? null) ?: 'https://www.instagram.com/falc.ons111';
        $social['facebook'] = ($social['facebook'] ?? null) ?: 'https://www.facebook.com/share/1VQrUgtut9/';
        DB::table('settings')->updateOrInsert(['key' => 'social'], ['value' => json_encode($social), 'updated_at' => now()]);
        Cache::forget('settings.all');
    }

    public function down(): void {}
};
