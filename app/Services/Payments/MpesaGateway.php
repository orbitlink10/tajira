<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MpesaGateway implements PaymentGateway
{
    public function __construct(private readonly MpesaClient $client) {}

    public function key(): string
    {
        return 'mpesa';
    }

    public function isEnabled(): bool
    {
        return $this->client->isConfigured();
    }

    public function initiate(Order $order, array $context = []): array
    {
        if (! $this->isEnabled()) {
            throw ValidationException::withMessages(['payment_method' => 'M-Pesa is not currently available.']);
        }

        $phone = (string) ($context['phone'] ?? $order->customer_phone);
        $this->client->normalisePhone($phone);

        try {
            return Cache::lock('payment-initiate:'.$order->id, 20)->block(5, function () use ($order, $phone) {
                $existing = Payment::query()
                    ->where('order_id', $order->id)
                    ->where('provider', 'mpesa')
                    ->where('status', Payment::STATUS_INITIATED)
                    ->latest('id')
                    ->first();

                if ($existing && $existing->initiated_at?->gt(now()->subMinutes(2))) {
                    return [
                        'payment' => $existing,
                        'message' => 'A payment request was already sent to the phone. Check the prompt or wait before retrying.',
                        'redirect' => null,
                    ];
                }

                $payment = Payment::query()->create([
                    'order_id' => $order->id,
                    'provider' => 'mpesa',
                    'method' => 'stk_push',
                    'status' => Payment::STATUS_PENDING,
                    'amount_minor' => $order->total_minor,
                    'currency' => $order->currency,
                    'phone' => $phone,
                    'idempotency_key' => 'order:'.$order->id.':mpesa:'.now()->timestamp.':'.Str::lower(Str::random(6)),
                ]);

                try {
                    $result = $this->client->stkPush($phone, $order->total_minor, $order->number, 'Tajira '.$order->number);
                } catch (RuntimeException $exception) {
                    $payment->update([
                        'status' => Payment::STATUS_FAILED,
                        'failure_reason' => $exception->getMessage(),
                        'completed_at' => now(),
                    ]);

                    throw ValidationException::withMessages(['payment_method' => $exception->getMessage()]);
                }

                $payment->update([
                    'status' => Payment::STATUS_INITIATED,
                    'provider_reference' => $result['checkout_request_id'],
                    'merchant_reference' => $result['merchant_request_id'],
                    'request_payload' => [
                        'merchant_request_id' => $result['merchant_request_id'],
                        'checkout_request_id' => $result['checkout_request_id'],
                        'response_code' => $result['response_code'],
                        'response_description' => $result['response_description'],
                        'amount_minor' => $order->total_minor,
                    ],
                    'response_payload' => $result,
                    'initiated_at' => now(),
                ]);

                return [
                    'payment' => $payment,
                    'message' => $result['customer_message'] ?: 'Check your phone to approve the M-Pesa payment.',
                    'redirect' => null,
                ];
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'payment_method' => 'A payment request is already being processed. Please wait a moment before retrying.',
            ]);
        }
    }
}
