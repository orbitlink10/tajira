<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Product;
use App\Services\SkuGenerator;
use App\Services\SlugService;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportCsv extends Command
{
    protected $signature = 'catalogue:import-csv {file} {--dry-run}';

    protected $description = 'Validate an owner-supplied product CSV, optionally creating draft products';

    public function handle(SkuGenerator $skus, SlugService $slugs): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error('CSV file not found.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $handle = fopen($path, 'r');
        $header = array_map(fn ($value) => Str::lower(trim((string) $value)), fgetcsv($handle) ?: []);

        if (! in_array('name', $header, true)) {
            fclose($handle);
            $this->error('CSV must include a name column.');

            return self::FAILURE;
        }

        $batch = ImportBatch::query()->create([
            'reference' => 'CSV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4)),
            'type' => 'csv',
            'source' => basename($path),
            'status' => $dryRun ? 'dry_run' : 'running',
            'started_at' => now(),
        ]);

        $rowNumber = 1;
        $created = 0;
        $failed = 0;

        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $payload = [];

            foreach ($header as $index => $key) {
                $payload[$key] = trim((string) ($values[$index] ?? ''));
            }

            $errors = [];

            if (blank($payload['name'] ?? null)) {
                $errors[] = 'Name is required.';
            }

            $brand = null;

            if (! empty($payload['brand'])) {
                $brand = Brand::query()->whereRaw('LOWER(name) = ?', [Str::lower($payload['brand'])])->first();

                if (! $brand) {
                    $errors[] = 'Brand not found.';
                }
            }

            if (! empty($payload['sku']) && Product::query()->where('sku', $payload['sku'])->exists()) {
                $errors[] = 'SKU already exists; existing products are not overwritten.';
            }

            ImportRow::query()->create([
                'import_batch_id' => $batch->id,
                'row_number' => $rowNumber,
                'payload' => $payload,
                'status' => $errors === [] ? ($dryRun ? 'valid' : 'imported') : 'error',
                'errors' => $errors,
            ]);

            if ($errors !== []) {
                $failed++;

                continue;
            }

            if (! $dryRun) {
                Product::query()->create([
                    'name' => $payload['name'],
                    'slug' => $slugs->unique($payload['name'], Product::class),
                    'sku' => $payload['sku'] ?: $skus->forProduct($brand?->name, $payload['model'] ?? null, $payload['name']),
                    'brand_id' => $brand?->id,
                    'model' => $payload['model'] ?? null,
                    'mpn' => $payload['mpn'] ?? null,
                    'price_minor' => Money::parseToMinor($payload['price_kes'] ?? null),
                    'stock_quantity' => (int) ($payload['stock'] ?? 0),
                    'product_type' => 'simple',
                    'status' => Product::STATUS_DRAFT,
                    'is_indexable' => true,
                ]);
                $created++;
            }
        }

        fclose($handle);

        $batch->update([
            'total_rows' => $rowNumber - 1,
            'processed_rows' => $rowNumber - 1,
            'imported_rows' => $created,
            'failed_rows' => $failed,
            'status' => $dryRun ? 'dry_run_complete' : 'completed',
            'finished_at' => now(),
        ]);

        $this->info(($dryRun ? 'Dry run' : 'Import').' finished. Drafts created: '.$created.'; errors: '.$failed.'.');
        $this->line('Batch: '.$batch->reference);

        return self::SUCCESS;
    }
}
