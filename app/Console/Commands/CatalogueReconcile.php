<?php

namespace App\Console\Commands;

use App\Services\Imports\CatalogueImportService;
use Illuminate\Console\Command;

class CatalogueReconcile extends Command
{
    protected $signature = 'catalogue:reconcile';

    protected $description = 'Print catalogue discovery and reconciliation metrics';

    public function handle(CatalogueImportService $imports): int
    {
        $metrics = $imports->metrics();

        foreach ($metrics as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $this->line(str_pad(str_replace('_', ' ', $key), 30).': '.$value);
        }

        $this->newLine();
        $this->info('Brand coverage (staged):');

        foreach ($metrics['brand_coverage'] as $row) {
            $this->line('  '.str_pad((string) $row->raw_brand, 26).' discovered '.str_pad((string) $row->discovered, 5).' drafts '.$row->imported);
        }

        return self::SUCCESS;
    }
}
