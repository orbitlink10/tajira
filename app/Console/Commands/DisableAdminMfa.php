<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DisableAdminMfa extends Command
{
    protected $signature = 'tajira:disable-admin-mfa {email}';

    protected $description = 'Emergency reset of an administrator MFA secret (server access required)';

    public function handle(): int
    {
        $user = User::query()->where('email', strtolower((string) $this->argument('email')))->first();

        if (! $user) {
            $this->error('Administrator not found.');

            return self::FAILURE;
        }

        if (! $this->confirm('Disable MFA for '.$user->email.'? Use only during a verified account recovery.')) {
            return self::FAILURE;
        }

        $user->forceFill(['mfa_secret' => null, 'mfa_enabled' => false])->save();
        $this->warn('MFA disabled for '.$user->email.'. Ask the administrator to set it up again immediately.');

        return self::SUCCESS;
    }
}
