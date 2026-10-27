<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Services\Imports\CatalogueImportService;
use Illuminate\Console\Command;
use Throwable;

class CatalogueFetch extends Command
{
    protected $signature = 'catalogue:fetch {batch : Import batch ID} {--limit=25}';

    protected $description = 'Fetch and parse staged source pages with rate limits and checkpoints';

    public function handle(CatalogueImportService $imports): int
    {
        $batch = ImportBatch::query()->find((int) $this->argument('batch'));

        if (! $batch) {
            $this->error('Import batch not found.');

            return self::FAILURE;
        }

        try {
            $result = $imports->fetchBatch($batch, (int) $this->option('limit'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Fetched '.$result['fetched'].'; failed '.$result['failed'].'; duplicates '.$result['duplicates'].'; remaining '.$result['remaining'].'.');

        return self::SUCCESS;
    }
}
