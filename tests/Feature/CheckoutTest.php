<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enablePickup();
    }

    public function test_checkout_reserves_stock_and_creates_order(): void
    {
        $product = $this->product(['price_minor' => 100000, 'stock_quantity' => 5]);
        $this->addToCart($product, 2);

        $cart = app(CartService::class);
        $order = app(CheckoutService::class)->placeOrder(
            $this->checkoutData(['delivery_type' => 'pickup']),
            $cart->payloadForCheckout(),
            null,
            'test-session'
        );

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame(200000, $order->subtotal_minor);
        $this->assertSame(200000, $order->total_minor);
        $this->assertCount(1, $order->items);
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'quantity' => 2, 'status' => 'active']);
        $this->assertSame(2, $product->fresh()->reserved_quantity);
    }

    public function test_payment_confirmation_commits_stock_and_is_idempotent(): void
    {
        $product = $this->product(['price_minor' => 100000, 'stock_quantity' => 5]);
        $this->addToCart($product);

        $order = app(CheckoutService::class)->placeOrder(
            $this->checkoutData(['delivery_type' => 'pickup']),
            app(CartService::class)->payloadForCheckout(),
            null,
            'test-session-2'
        );

        $admin = $this->admin();
        $payment = app(PaymentManager::class)->manualConfirm($order, $admin, 'Verified bank transfer', 'REF-1');

        $order->refresh();
        $product->refresh();

        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertNotNull($order->stock_committed_at);
        $this->assertSame(4, $product->stock_quantity);
        $this->assertSame(0, $product->reserved_quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'type' => 'sale', 'quantity' => -1]);

        $second = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'mpesa',
            'method' => 'stk_push',
            'status' => Payment::STATUS_INITIATED,
            'amount_minor' => $order->total_minor,
            'currency' => $order->currency,
            'provider_reference' => 'duplicate-checkout-test',
            'idempotency_key' => 'duplicate-checkout-test',
            'initiated_at' => now(),
        ]);

        app(PaymentManager::class)->markPaid($second, 'duplicate-checkout-test', ['source' => 'test']);

        $this->assertSame(4, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('order_status_history', ['order_id' => $order->id, 'status' => 'duplicate_payment_exception']);

        $this->expectException(ValidationException::class);
        app(PaymentManager::class)->manualConfirm($order->fresh(), $admin, 'Duplicate confirmation');
    }

    public function test_checkout_rejects_overselling_when_stock_is_reserved(): void
    {
        $product = $this->product(['price_minor' => 100000, 'stock_quantity' => 1]);
        $lines = [['product' => $product, 'variant' => null, 'quantity' => 1]];

        app(CheckoutService::class)->placeOrder($this->checkoutData(), $lines, null, 'test-oversell-1');

        try {
            app(CheckoutService::class)->placeOrder($this->checkoutData(), $lines, null, 'test-oversell-2');
            $this->fail('Expected a validation exception for overselling.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('available', strtolower(collect($exception->errors())->flatten()->first()));
        }

        $this->assertSame(1, $product->fresh()->reserved_quantity);
    }
}
