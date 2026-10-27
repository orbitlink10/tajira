<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BankTransferGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'bank_transfer';
    }

    public function isEnabled(): bool
    {
        return (bool) setting('payments.bank_enabled', config('tajira.payments.bank_transfer.enabled'))
            && filled(setting('payments.bank_account_number', config('tajira.payments.bank_transfer.account_number')));
    }

    public function initiate(Order $order, array $context = []): array
    {
        if (! $this->isEnabled()) {
            throw ValidationException::withMessages(['payment_method' => 'Bank transfer is not currently available.']);
        }

        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider', 'bank_transfer')
            ->where('status', Payment::STATUS_PENDING)
            ->latest('id')
            ->first();

        if (! $payment) {
            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'provider' => 'bank_transfer',
                'method' => 'manual_transfer',
                'status' => Payment::STATUS_PENDING,
                'amount_minor' => $order->total_minor,
                'currency' => $order->currency,
                'idempotency_key' => 'order:'.$order->id.':bank_transfer:'.now()->timestamp.':'.Str::lower(Str::random(6)),
                'initiated_at' => now(),
            ]);
        }

        return [
            'payment' => $payment,
            'message' => 'Transfer the exact amount using your order number as the reference. Your order is confirmed only after our team verifies the payment.',
            'redirect' => route('payments.show', $order->number),
        ];
    }
}
