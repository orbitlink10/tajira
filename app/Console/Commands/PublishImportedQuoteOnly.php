<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\SourceProduct;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishImportedQuoteOnly extends Command
{
    protected $signature = 'catalogue:publish-quote-only {--force : Skip the confirmation prompt}';

    protected $description = 'Publish imported catalogue records as noindexed quotation-only products';

    public function handle(): int
    {
        $importedIds = SourceProduct::query()
            ->where('status', 'imported')
            ->whereNotNull('product_id')
            ->pluck('product_id');

        $query = Product::query()
            ->whereIn('id', $importedIds)
            ->where('status', Product::STATUS_DRAFT);

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No draft imported products to publish.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Publish '.$count.' imported products as quotation-only, noindexed catalogue records?')) {
            return self::FAILURE;
        }

        $admin = User::query()->where('role', User::ROLE_SUPER_ADMIN)->where('is_active', true)->first();

        DB::transaction(function () use ($query, $admin, $importedIds) {
            $query->update([
                'status' => Product::STATUS_PUBLISHED,
                'published_at' => now(),
                'price_on_request' => true,
                'is_indexable' => false,
                'approved_by' => $admin?->id,
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

            SourceProduct::query()
                ->whereIn('product_id', $importedIds)
                ->where('status', 'imported')
                ->whereNull('published_at')
                ->update(['published_at' => now()]);

            AuditLog::query()->create([
                'user_id' => $admin?->id,
                'event' => 'catalogue.published-quote-only',
                'new_values' => ['products' => $importedIds->count(), 'mode' => 'quotation-only, noindexed'],
                'created_at' => now(),
            ]);
        });

        Cache::forget('tajira.home.categories');
        Cache::forget('tajira.home.featured');
        Cache::forget('tajira.menu.header');
        app(SitemapService::class)->flush();

        $this->info($count.' imported products published as quotation-only, noindexed catalogue records.');

        return self::SUCCESS;
    }
}
