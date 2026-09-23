<?php

namespace Tests;

use App\Enums\CompetitionStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Competition;
use App\Models\Leader;
use App\Models\Round;
use App\Models\User;
use App\Services\LeaderService;
use App\Services\RoundService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function competition(array $config = []): Competition
    {
        return Competition::create([
            'name' => 'Test Competition',
            'status' => CompetitionStatus::Active,
            'configuration' => array_merge(Competition::DEFAULT_CONFIG, $config),
        ]);
    }

    protected function runningRound(Competition $competition, int $seconds = 3600): Round
    {
        $rounds = app(RoundService::class);

        return $rounds->start($rounds->create($competition, ['duration_seconds' => $seconds]));
    }

    protected function leader(Competition $competition, string $phone = '01000000001', ?string $name = null, bool $account = false): Leader
    {
        return app(LeaderService::class)->create($competition, [
            'name' => $name ?? 'Leader '.substr($phone, -3),
            'phone' => $phone,
            'email' => 'l'.preg_replace('/\D/', '', $phone).'@example.com',
        ], $account ? 'secret123' : null, null, 'active');
    }

    protected function superAdmin(): User
    {
        return User::create(['name' => 'Root', 'email' => 'root'.uniqid().'@example.com', 'password' => 'secret123', 'role' => Role::SuperAdmin, 'is_active' => true]);
    }

    protected function reviewer(?array $permissions = null): User
    {
        return User::create([
            'name' => 'Reviewer', 'email' => 'rev'.uniqid().'@example.com', 'password' => 'secret123', 'role' => Role::Admin,
            'permissions' => $permissions ?? Permission::reviewerDefaults(), 'is_active' => true,
        ]);
    }

    /** A fake transfer screenshot / photo upload. */
    protected function image(string $name = 'transfer.jpg', int $w = 600, int $h = 900): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h);
    }

    /** Full participant form payload for POST /register. */
    protected function registerForm(string $ref, array $overrides = []): array
    {
        return array_merge(['ref' => $ref, 'full_name' => 'Sara Ali', 'phone' => '01112223334', 'email' => 'sara@example.com', 'city' => 'Cairo', 'team' => 'Million Team', 'consent' => '1', 'transfer_screenshot' => $this->image()], $overrides);
    }

    protected function participant(int $n, array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Participant Name',
            'phone' => '0111'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'email' => "p{$n}@example.com",
            'city' => 'Cairo',
            'team' => 'Million Team',
        ], $overrides);
    }
}
