<?php

namespace App\Console\Commands;

use App\Services\Imports\CatalogueImportService;
use Illuminate\Console\Command;

class CatalogueRecheckDuplicates extends Command
{
    protected $signature = 'catalogue:recheck-duplicates';

    protected $description = 'Reset and recompute possible duplicate flags for staged catalogue records';

    public function handle(CatalogueImportService $imports): int
    {
        $result = $imports->recheckDuplicates();
        $this->info($result['duplicates'].' probable duplicate(s) flagged.');

        return self::SUCCESS;
    }
}
