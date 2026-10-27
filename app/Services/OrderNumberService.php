<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Quotation;

class OrderNumberService
{
    public function order(): string
    {
        $prefix = config('tajira.checkout.order_prefix', 'TJ');

        do {
            $number = $prefix.'-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }

    public function quotation(): string
    {
        $prefix = config('tajira.checkout.quote_prefix', 'QT');

        do {
            $number = $prefix.'-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
        } while (Quotation::query()->where('number', $number)->exists());

        return $number;
    }
}
