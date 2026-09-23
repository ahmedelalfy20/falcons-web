<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', Role::SuperAdmin->value)->exists()) {
            return;
        }
        $email = env('ADMIN_EMAIL', 'admin@falcons-organization.com');
        $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);

        User::create([
            'name' => env('ADMIN_NAME', 'Super Admin'),
            'email' => $email,
            'password' => $password,
            'role' => Role::SuperAdmin,
            'is_active' => true,
        ]);

        $this->command?->warn("Super admin created: {$email}".(env('ADMIN_PASSWORD') ? '' : " / password: {$password}  (change it after first login)"));
    }
}
