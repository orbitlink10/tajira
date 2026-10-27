<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class PublishScheduledProducts extends Command
{
    protected $signature = 'products:publish-scheduled';

    protected $description = 'Publish products whose scheduled publication time has passed';

    public function handle(): int
    {
        $count = Product::query()
            ->where('status', Product::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->update([
                'status' => Product::STATUS_PUBLISHED,
                'published_at' => now(),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        $this->info($count.' scheduled product(s) published.');

        return self::SUCCESS;
    }
}
