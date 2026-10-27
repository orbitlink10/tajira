<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! env('TAJIRA_SEED_DEMO_USERS', false)) {
            $this->command?->warn('Skipping administrator seeding in production. Run: php artisan tajira:create-admin');

            return;
        }

        $password = (string) (env('TAJIRA_SEED_ADMIN_PASSWORD') ?: 'admin123');
        $accounts = [
            ['name' => 'Super Administrator', 'email' => env('TAJIRA_SEED_ADMIN_EMAIL', 'admin@demo.com'), 'role' => User::ROLE_SUPER_ADMIN],
            ['name' => 'Catalogue Manager', 'email' => 'catalogue@tajira.test', 'role' => User::ROLE_CATALOGUE_MANAGER],
            ['name' => 'Content Editor', 'email' => 'content@tajira.test', 'role' => User::ROLE_CONTENT_EDITOR],
            ['name' => 'Order Manager', 'email' => 'orders@tajira.test', 'role' => User::ROLE_ORDER_MANAGER],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->firstOrCreate(
                ['email' => strtolower($account['email'])],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($password),
                    'role' => $account['role'],
                    'is_active' => true,
                ]
            );

            if ($user->wasRecentlyCreated) {
                $this->command?->info('Created '.$account['role'].' account: '.$account['email']);
            }
        }

        if (app()->environment('local', 'testing')) {
            $this->command?->warn('Demo administrator password is set from TAJIRA_SEED_ADMIN_PASSWORD. Change it before any shared or public deployment, and enable MFA.');
        }
    }
}
