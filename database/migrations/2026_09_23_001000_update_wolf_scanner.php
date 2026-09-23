<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Replace the Wolf scanner's description, features and screenshots with the new version. */
return new class extends Migration
{
    public function up(): void
    {
        $wolf = require database_path('data/wolf.php');
        DB::table('scanners')->where('slug', 'wolf')->update([
            ...collect($wolf)->except(['features', 'images'])->all(),
            'features' => json_encode($wolf['features'], JSON_UNESCAPED_UNICODE),
            'images' => json_encode($wolf['images']),
            'updated_at' => now(),
        ]);
    }

    public function down(): void {}
};
