<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EraseCustomer extends Command
{
    protected $signature = 'customers:erase {email}';

    protected $description = 'Anonymise a customer account after an approved deletion request';

    public function handle(): int
    {
        $user = User::query()->where('email', strtolower((string) $this->argument('email')))->first();

        if (! $user) {
            $this->error('Customer not found.');

            return self::FAILURE;
        }

        if (! $this->confirm('Anonymise '.$user->email.' and detach personal data? Order financial records will be retained in anonymised form.')) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($user) {
            $anonymousEmail = 'deleted-'.$user->id.'@invalid.local';

            $user->addresses()->delete();
            $user->wishlistItems()->delete();
            $user->reviews()->update([
                'user_id' => null,
                'author_name' => 'Deleted customer',
                'author_email' => null,
            ]);
            $user->orders()->update([
                'user_id' => null,
                'customer_name' => 'Deleted customer',
                'customer_email' => $anonymousEmail,
                'customer_phone' => 'redacted',
                'address_line' => 'redacted',
                'town' => 'redacted',
                'delivery_notes' => null,
                'notes' => null,
                'ip_hash' => null,
            ]);
            $user->quotations()->update([
                'user_id' => null,
                'name' => 'Deleted customer',
                'email' => $anonymousEmail,
                'phone' => 'redacted',
                'company' => null,
                'message' => null,
                'admin_notes' => null,
                'ip_hash' => null,
            ]);

            $user->forceFill([
                'name' => 'Deleted customer',
                'email' => $anonymousEmail,
                'phone' => null,
                'password' => Hash::make(Str::random(40)),
                'is_active' => false,
                'deletion_requested_at' => now(),
            ])->save();
            $user->delete();
        });

        $this->info('Customer account anonymised. Review retention obligations and backups separately.');

        return self::SUCCESS;
    }
}
