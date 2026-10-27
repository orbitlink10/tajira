<?php

namespace App\Console\Commands;

use App\Services\Imports\ManufacturerImageImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportManufacturerImages extends Command
{
    protected $signature = 'manufacturer-images:import {csv} {--dry-run}';

    protected $description = 'Attach authorised manufacturer images from a CSV mapping of SKU to manufacturer page/image URL';

    public function handle(ManufacturerImageImporter $importer): int
    {
        $path = (string) $this->argument('csv');

        if (! is_file($path)) {
            $this->error('CSV file not found.');

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = array_map(fn ($value) => Str::lower(trim((string) $value)), fgetcsv($handle) ?: []);

        if (! in_array('sku', $header, true)) {
            fclose($handle);
            $this->error('CSV must include a sku column.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $imported = 0;
        $failed = 0;
        $rowNumber = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $key) {
                $row[$key] = trim((string) ($values[$index] ?? ''));
            }

            if (empty($row['rights_note'])) {
                $this->warn('Row '.$rowNumber.': skipped because rights_note is required.');
                $failed++;

                continue;
            }

            if ($dryRun) {
                $this->line('Row '.$rowNumber.': would import image for '.$row['sku']);
                $imported++;

                continue;
            }

            try {
                $image = $importer->import($row);
                $this->info('Row '.$rowNumber.': attached image #'.$image->id.' to '.$row['sku']);
                $imported++;
            } catch (\Throwable $exception) {
                $failed++;
                $this->warn('Row '.$rowNumber.': '.$exception->getMessage());
            }
        }

        fclose($handle);

        $this->info(($dryRun ? 'Dry run complete. ' : '').'Imported: '.$imported.'; failed: '.$failed.'.');

        return $failed > 0 && $imported === 0 ? self::FAILURE : self::SUCCESS;
    }
}
