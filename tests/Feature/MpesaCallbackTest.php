<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class MpesaCallbackTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enablePickup();

        config()->set('tajira.payments.mpesa.enabled', true);
        config()->set('tajira.payments.mpesa.env', 'sandbox');
        config()->set('tajira.payments.mpesa.consumer_key', 'test-key');
        config()->set('tajira.payments.mpesa.consumer_secret', 'test-secret');
        config()->set('tajira.payments.mpesa.shortcode', '174379');
        config()->set('tajira.payments.mpesa.passkey', 'test-passkey');
        config()->set('tajira.payments.mpesa.callback_url', 'https://example.test/payments/mpesa/callback');

        Http::fake([
            'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'token', 'expires_in' => 3599]),
            'sandbox.safaricom.co.ke/mpesa/stkpushquery/*' => Http::response([
                'ResultCode' => '0',
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 1000],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCPT123'],
                    ],
                ],
            ]),
        ]);
    }

    public function test_successful_callback_is_server_verified_commits_stock_and_is_idempotent(): void
    {
        [$order, $product, $payment] = $this->pendingMpesaOrder();

        $payload = [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'merchant-1',
                    'CheckoutRequestID' => 'ws_CO_123',
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1000],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCPT123'],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/payments/mpesa/callback', $payload)->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock_quantity);

        $this->postJson('/payments/mpesa/callback', $payload)->assertOk();
        $this->assertSame(4, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_amount_mismatch_does_not_mark_order_paid(): void
    {
        [$order, , $payment] = $this->pendingMpesaOrder();

        $this->postJson('/payments/mpesa/callback', [
            'Body' => ['stkCallback' => [
                'MerchantRequestID' => 'merchant-2',
                'CheckoutRequestID' => 'ws_CO_123',
                'ResultCode' => 0,
                'ResultDesc' => 'Success',
                'CallbackMetadata' => ['Item' => [
                    ['Name' => 'Amount', 'Value' => 999],
                    ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCPT999'],
                ]],
            ]],
        ])->assertOk();

        $this->assertNotSame('paid', $order->fresh()->payment_status);
        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
    }

    public function test_payment_received_after_cancellation_is_recorded_for_manual_review_without_committing_stock(): void
    {
        [$order, $product, $payment] = $this->pendingMpesaOrder();

        $order->update(['status' => Order::STATUS_CANCELLED, 'payment_status' => 'cancelled', 'cancelled_at' => now()]);
        $order->reservations()->update(['status' => 'released']);
        $product->update(['reserved_quantity' => 0]);

        app(PaymentManager::class)->markPaid($payment, 'ws_CO_123', ['source' => 'late-test']);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->status);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('order_status_history', ['order_id' => $order->id, 'status' => 'payment_exception']);
    }

    private function pendingMpesaOrder(): array
    {
        $product = $this->product(['price_minor' => 100000, 'stock_quantity' => 5]);
        $lines = [['product' => $product, 'variant' => null, 'quantity' => 1]];
        $order = app(CheckoutService::class)->placeOrder($this->checkoutData(), $lines, null, 'mpesa-'.uniqid());

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'mpesa',
            'method' => 'stk_push',
            'status' => Payment::STATUS_INITIATED,
            'amount_minor' => 100000,
            'currency' => 'KES',
            'phone' => '254712345678',
            'provider_reference' => 'ws_CO_123',
            'merchant_reference' => 'merchant-1',
            'initiated_at' => now(),
        ]);

        return [$order, $product, $payment];
    }
}
