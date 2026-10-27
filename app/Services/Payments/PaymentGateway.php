<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    public function key(): string;

    public function isEnabled(): bool;

    /**
     * @return array{payment:Payment,message:string,redirect:?string}
     */
    public function initiate(Order $order, array $context = []): array;
}
