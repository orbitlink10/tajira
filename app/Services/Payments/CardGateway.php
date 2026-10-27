<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Validation\ValidationException;

class CardGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'card';
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function initiate(Order $order, array $context = []): array
    {
        throw ValidationException::withMessages([
            'payment_method' => 'Card payments are not enabled. Add an approved provider adapter before enabling this option.',
        ]);
    }
}
