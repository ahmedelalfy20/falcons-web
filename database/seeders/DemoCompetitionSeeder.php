<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Competition;
use App\Models\User;
use App\Services\LeaderService;
use App\Services\RegistrationService;
use App\Services\RoundService;
use Illuminate\Database\Seeder;

/** Local demo data: run with SEED_DEMO=true php artisan db:seed */
class DemoCompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $competition = Competition::active();
        $rounds = app(RoundService::class);
        $round = $competition->currentRound();
        if ($round->status->value === 'ready') {
            $round = $rounds->start($round);
        }

        $reviewer = User::firstOrCreate(['email' => 'reviewer@example.com'], [
            'name' => 'Demo Reviewer', 'password' => 'password123', 'role' => Role::Admin,
            'permissions' => Permission::reviewerDefaults(), 'is_active' => true,
        ]);

        $leaders = app(LeaderService::class);
        $names = ['Omar Khaled', 'Nour Hassan', 'Youssef Adel', 'Salma Tarek', 'Karim Mostafa', 'Laila Samir'];
        $made = [];
        foreach ($names as $i => $name) {
            $email = 'leader'.($i + 1).'@example.com';
            if (User::where('email', $email)->exists()) {
                continue;
            }
            $made[] = $leaders->create($competition, ['name' => $name, 'phone' => '0100000000'.$i, 'email' => $email], 'password123');
        }

        $regs = app(RegistrationService::class);
        $n = 0;
        foreach ($made as $li => $leader) {
            $count = [9, 6, 6, 4, 2, 1][$li] ?? 1;
            for ($k = 0; $k < $count; $k++) {
                $n++;
                $r = $regs->submit($leader->unique_code, ['full_name' => "Participant {$n}", 'phone' => '0111'.str_pad((string) $n, 7, '0', STR_PAD_LEFT), 'email' => "p{$n}@example.com", 'city' => 'Cairo']);
                if ($k % 4 !== 3) {
                    $k % 5 === 4 ? $regs->reject($r, $reviewer) : $regs->accept($r, $reviewer);
                }
            }
        }
    }
}
