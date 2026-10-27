<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\StockReservation;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class ReservationExpiryTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enablePickup();
    }

    public function test_expired_reservations_release_stock_and_cancel_unpaid_order(): void
    {
        $product = $this->product(['price_minor' => 100000, 'stock_quantity' => 1]);
        $lines = [['product' => $product, 'variant' => null, 'quantity' => 1]];

        $order = app(CheckoutService::class)->placeOrder($this->checkoutData(), $lines, null, 'expiry-test');

        StockReservation::query()->where('order_id', $order->id)->update(['expires_at' => now()->subMinute()]);

        $released = app(InventoryService::class)->releaseExpired();

        $this->assertSame(1, $released);
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(0, $product->fresh()->reserved_quantity);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'expired']);
    }
}
