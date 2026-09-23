<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([ContentSeeder::class, AdminSeeder::class, CompetitionSeeder::class]);

        if (app()->environment('local') && env('SEED_DEMO', false)) {
            $this->call(DemoCompetitionSeeder::class);
        }
    }
}
