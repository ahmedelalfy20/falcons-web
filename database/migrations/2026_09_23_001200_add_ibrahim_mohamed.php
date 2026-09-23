<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Add Ibrahim Mohamed to the leaders. */
return new class extends Migration
{
    public function up(): void
    {
        $m = require database_path('data/ibrahim.php');
        $m['gallery'] = json_encode($m['gallery']);
        if (! DB::table('team_members')->where('slug', $m['slug'])->exists()) {
            DB::table('team_members')->insert($m + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('team_members')->where('slug', 'ibrahim-mohamed')->delete();
    }
};
