<?php

namespace Database\Seeders;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Services\RoundService;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        if (Competition::exists()) {
            return;
        }
        $competition = Competition::create([
            'name' => 'Leader Referral Competition',
            'name_ar' => 'مسابقة القادة للإحالة',
            'status' => CompetitionStatus::Active,
            'configuration' => Competition::DEFAULT_CONFIG,
        ]);
        app(RoundService::class)->create($competition, ['duration_seconds' => 24 * 3600]);
    }
}
