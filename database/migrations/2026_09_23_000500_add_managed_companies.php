<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** The companies under Falcons' management (logos ship in public/media/companies). */
return new class extends Migration
{
    public const COMPANIES = [
        ['name' => '3AQRAB', 'name_ar' => 'عقرب', 'logo' => 'media/companies/3aqrab.webp'],
        ['name' => 'Mega Team', 'name_ar' => 'ميجا تيم', 'logo' => 'media/companies/mega-team.webp'],
        ['name' => 'Million Team', 'name_ar' => 'مليون تيم', 'logo' => 'media/companies/million-team.webp'],
    ];

    public function up(): void
    {
        foreach (self::COMPANIES as $i => $c) {
            if (! DB::table('companies')->where('logo', $c['logo'])->exists()) {
                DB::table('companies')->insert($c + ['sort_order' => $i + 1, 'is_active' => true, 'on_light' => false, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('companies')->whereIn('logo', array_column(self::COMPANIES, 'logo'))->delete();
    }
};
