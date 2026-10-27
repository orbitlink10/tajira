<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'tajira:create-admin {email} {--name=} {--role=super_admin}';

    protected $description = 'Create or update a Tajira administrator account';

    public function handle(): int
    {
        $roles = User::adminRoles();
        $role = (string) $this->option('role');

        if (! in_array($role, $roles, true)) {
            $this->error('Invalid role. Use one of: '.implode(', ', $roles));

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (minimum 12 characters, mixed case, number and symbol)');
        $confirmation = (string) $this->secret('Confirm password');

        if ($password !== $confirmation) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $password], [
            'password' => ['required', 'min:12', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $this->option('name') ?: ucwords(str_replace('_', ' ', $role)),
                'password' => Hash::make($password),
                'role' => $role,
                'is_active' => true,
                'deletion_requested_at' => null,
            ]
        );

        if ($user->trashed()) {
            $user->restore();
        }

        $this->info('Administrator ready: '.$user->email.' ('.$user->roleLabel().'). Enable MFA after signing in.');

        return self::SUCCESS;
    }
}
