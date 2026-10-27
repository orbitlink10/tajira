<?php

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'stock:release-expired';

    protected $description = 'Release expired stock reservations and cancel unpaid orders';

    public function handle(InventoryService $inventory): int
    {
        $count = $inventory->releaseExpired();
        $this->info($count.' expired reservation(s) released.');

        return self::SUCCESS;
    }
}
