<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Habiba Ayman: 4 years of experience. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('team_members')->where('slug', 'habiba-ayman')->update(['experience_years' => 4, 'updated_at' => now()]);
    }

    public function down(): void {}
};
