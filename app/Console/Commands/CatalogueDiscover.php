<?php

namespace App\Console\Commands;

use App\Services\Imports\CatalogueImportService;
use Illuminate\Console\Command;
use Throwable;

class CatalogueDiscover extends Command
{
    protected $signature = 'catalogue:discover {--limit=1000} {--source=} {--user=}';

    protected $description = 'Stage product URLs from the approved reference sitemap without publishing anything';

    public function handle(CatalogueImportService $imports): int
    {
        try {
            $batch = $imports->discoverSitemap(
                $this->option('user') ? (int) $this->option('user') : null,
                (int) $this->option('limit'),
                $this->option('source') ?: null
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Discovery batch '.$batch->reference.' staged '.$batch->total_rows.' URL(s).');
        $this->line('Fetch pages with: php artisan catalogue:fetch '.$batch->id.' --limit=25');

        return self::SUCCESS;
    }
}
