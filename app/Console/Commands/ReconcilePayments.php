<?php

namespace App\Console\Commands;

use App\Services\Payments\PaymentManager;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Query pending M-Pesa payments for a definitive server-side status';

    public function handle(PaymentManager $payments): int
    {
        $count = $payments->reconcilePending();
        $this->info($count.' pending payment(s) checked.');

        return self::SUCCESS;
    }
}
