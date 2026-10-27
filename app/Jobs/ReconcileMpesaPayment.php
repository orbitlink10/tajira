<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileMpesaPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 180, 300, 600];

    public function __construct(public int $paymentId) {}

    public function handle(PaymentManager $payments): void
    {
        $payment = Payment::query()->find($this->paymentId);

        if (! $payment || $payment->isSuccessful()) {
            return;
        }

        $status = $payments->confirmByQuery($payment);

        if ($status === 'pending' && $this->attempts() < $this->tries) {
            $this->release($this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);
        }
    }
}
