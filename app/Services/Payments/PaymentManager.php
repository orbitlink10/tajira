<?php

namespace App\Services\Payments;

use App\Jobs\ReconcileMpesaPayment;
use App\Mail\PaymentReceived;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\ProductReview;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentManager
{
    public function __construct(
        private readonly MpesaGateway $mpesa,
        private readonly BankTransferGateway $bank,
        private readonly CardGateway $card,
        private readonly MpesaClient $mpesaClient,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * @return array<string, string>
     */
    public function enabledMethods(): array
    {
        $methods = [];

        if ($this->mpesa->isEnabled()) {
            $methods['mpesa'] = 'M-Pesa';
        }

        if ($this->bank->isEnabled()) {
            $methods['bank_transfer'] = 'Bank transfer';
        }

        if ($this->card->isEnabled()) {
            $methods['card'] = 'Card';
        }

        return $methods;
    }

    public function gateway(string $method): PaymentGateway
    {
        return match ($method) {
            'mpesa' => $this->mpesa,
            'bank_transfer' => $this->bank,
            'card' => $this->card,
            default => throw ValidationException::withMessages(['payment_method' => 'Choose a valid payment method.']),
        };
    }

    /**
     * @return array{payment:Payment,message:string,redirect:?string}
     */
    public function initiate(Order $order, string $method, array $context = []): array
    {
        if ($order->isPaid()) {
            throw ValidationException::withMessages(['payment_method' => 'This order is already paid.']);
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['payment_method' => 'This order has been cancelled.']);
        }

        $result = $this->gateway($method)->initiate($order, $context);

        $order->update(['payment_method' => $method]);

        return $result;
    }

    public function handleMpesaCallback(array $payload, ?string $ip = null): array
    {
        $callback = $payload['Body']['stkCallback'] ?? null;

        if (! is_array($callback)) {
            Log::warning('M-Pesa callback missing stkCallback', ['ip' => $ip]);

            return ['status' => 'invalid'];
        }

        $checkoutRequestId = (string) ($callback['CheckoutRequestID'] ?? '');
        $merchantRequestId = (string) ($callback['MerchantRequestID'] ?? '');
        $resultCode = (int) ($callback['ResultCode'] ?? -1);
        $resultDesc = (string) ($callback['ResultDesc'] ?? '');

        if ($checkoutRequestId === '') {
            return ['status' => 'invalid'];
        }

        $metadata = collect($callback['CallbackMetadata']['Item'] ?? [])
            ->mapWithKeys(fn ($item) => [($item['Name'] ?? '') => $item['Value'] ?? null])
            ->all();

        $receipt = isset($metadata['MpesaReceiptNumber']) ? (string) $metadata['MpesaReceiptNumber'] : null;
        $amountMinor = isset($metadata['Amount']) ? (int) round(((float) $metadata['Amount']) * 100) : null;

        $eventId = 'mpesa:'.$checkoutRequestId.':'.$resultCode.':'.($receipt ?? 'none');
        $event = PaymentEvent::query()->firstOrCreate(
            ['event_id' => $eventId],
            [
                'provider' => 'mpesa',
                'type' => $resultCode === 0 ? 'payment.success' : 'payment.failure',
                'payload' => ['result_code' => $resultCode, 'result_desc' => $resultDesc, 'metadata' => $metadata],
            ]
        );

        if ($event->processed_at !== null) {
            return ['status' => 'duplicate'];
        }

        $payment = Payment::query()->where('provider_reference', $checkoutRequestId)->first();

        if (! $payment) {
            $event->update(['processed_at' => now()]);

            Log::warning('M-Pesa callback for unknown payment', ['checkout_request_id' => $checkoutRequestId]);

            return ['status' => 'unknown_payment'];
        }

        $event->update(['payment_id' => $payment->id]);

        if ($resultCode !== 0) {
            $this->markFailed($payment, $resultDesc ?: 'M-Pesa payment was not completed.', [
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
            ]);
            $event->update(['processed_at' => now()]);

            return ['status' => 'failed'];
        }

        if ($amountMinor === null || $amountMinor !== $payment->amount_minor) {
            $this->markFailed($payment, 'The paid amount did not match the order total.', [
                'expected_minor' => $payment->amount_minor,
                'received_minor' => $amountMinor,
            ]);
            $event->update(['processed_at' => now()]);

            return ['status' => 'amount_mismatch'];
        }

        try {
            $query = $this->mpesaClient->queryStatus($checkoutRequestId);
        } catch (Throwable $exception) {
            Log::info('M-Pesa callback received; status query deferred', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);

            ReconcileMpesaPayment::dispatch($payment->id)->delay(now()->addMinutes(2));
            $event->update(['processed_at' => now()]);

            return ['status' => 'pending_confirmation'];
        }

        if ($query['result_code'] !== '0') {
            $this->markFailed($payment, $query['result_desc'] ?: 'M-Pesa did not confirm the transaction.', $query['raw']);
            $event->update(['processed_at' => now()]);

            return ['status' => 'failed'];
        }

        if ($query['amount_minor'] !== null && $query['amount_minor'] !== $payment->amount_minor) {
            $this->markFailed($payment, 'The confirmed amount did not match the order total.', $query['raw']);
            $event->update(['processed_at' => now()]);

            return ['status' => 'amount_mismatch'];
        }

        $this->markPaid($payment, $checkoutRequestId, [
            'source' => 'mpesa_callback',
            'merchant_request_id' => $merchantRequestId,
            'checkout_request_id' => $checkoutRequestId,
            'receipt' => $query['receipt'] ?? $receipt,
            'result_code' => $resultCode,
        ]);

        $event->update(['processed_at' => now()]);

        return ['status' => 'paid'];
    }

    public function confirmByQuery(Payment $payment): string
    {
        if ($payment->provider !== 'mpesa' || ! $payment->provider_reference) {
            return $payment->status;
        }

        if ($payment->isSuccessful()) {
            return 'paid';
        }

        try {
            $query = $this->mpesaClient->queryStatus($payment->provider_reference);
        } catch (Throwable $exception) {
            Log::info('M-Pesa reconciliation query pending', ['payment_id' => $payment->id]);

            return 'pending';
        }

        if ($query['result_code'] === '0') {
            if ($query['amount_minor'] !== null && $query['amount_minor'] !== $payment->amount_minor) {
                $this->markFailed($payment, 'The confirmed amount did not match the order total.', $query['raw']);

                return 'amount_mismatch';
            }

            $this->markPaid($payment, $payment->provider_reference, [
                'source' => 'reconciliation',
                'receipt' => $query['receipt'],
                'result_code' => $query['result_code'],
            ]);

            return 'paid';
        }

        $this->markFailed($payment, $query['result_desc'] ?: 'M-Pesa did not confirm the transaction.', $query['raw']);

        return 'failed';
    }

    public function reconcilePending(): int
    {
        $count = 0;

        Payment::query()
            ->where('provider', 'mpesa')
            ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING])
            ->where('initiated_at', '<=', now()->subMinutes(2))
            ->orderBy('id')
            ->chunkById(50, function ($payments) use (&$count) {
                foreach ($payments as $payment) {
                    $this->confirmByQuery($payment);
                    $count++;
                }
            });

        return $count;
    }

    public function manualConfirm(Order $order, User $user, ?string $note = null, ?string $reference = null): Payment
    {
        if ($order->isPaid()) {
            throw ValidationException::withMessages(['order' => 'This order is already marked as paid.']);
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'order' => 'This order is cancelled. A received payment must be reconciled and refunded or re-quoted manually.',
            ]);
        }

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'manual',
            'method' => 'manual_reconciliation',
            'status' => Payment::STATUS_PENDING,
            'amount_minor' => $order->total_minor,
            'currency' => $order->currency,
            'provider_reference' => $reference,
            'idempotency_key' => 'manual:'.$order->id.':'.now()->timestamp.':'.Str::lower(Str::random(6)),
            'initiated_at' => now(),
        ]);

        $this->markPaid($payment, $reference, [
            'source' => 'admin_manual_confirmation',
            'confirmed_by' => $user->id,
            'note' => $note,
        ]);

        return $payment->fresh();
    }

    public function markPaid(Payment $payment, ?string $providerReference, array $payload = []): void
    {
        $outcome = DB::transaction(function () use ($payment, $providerReference, $payload) {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Payment::STATUS_SUCCEEDED && $order->payment_status === 'paid') {
                return false;
            }

            if ($locked->amount_minor !== $order->total_minor || $locked->currency !== $order->currency) {
                $locked->update([
                    'status' => Payment::STATUS_FAILED,
                    'failure_reason' => 'Payment amount or currency did not match the order.',
                    'completed_at' => now(),
                ]);

                throw ValidationException::withMessages([
                    'payment' => 'The payment amount does not match the order total. The order has not been marked paid.',
                ]);
            }

            if ($order->payment_status === 'paid' && $locked->status !== Payment::STATUS_SUCCEEDED) {
                $locked->update([
                    'status' => Payment::STATUS_SUCCEEDED,
                    'provider_reference' => $providerReference ?: $locked->provider_reference,
                    'response_payload' => array_merge($locked->response_payload ?? [], $payload),
                    'completed_at' => now(),
                    'failure_reason' => null,
                ]);

                $order->update([
                    'admin_notes' => trim(($order->admin_notes ? $order->admin_notes."\n" : '')
                        .'Duplicate payment received after the order was already paid. Verify and refund the extra payment.'),
                ]);

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'status' => 'duplicate_payment_exception',
                    'note' => 'A second successful payment was received for an order that was already paid. No additional stock was committed. Refund review required.',
                    'user_id' => null,
                    'created_at' => now(),
                ]);

                return 'duplicate';
            }

            $locked->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'provider_reference' => $providerReference ?: $locked->provider_reference,
                'response_payload' => array_merge($locked->response_payload ?? [], $payload),
                'completed_at' => now(),
                'failure_reason' => null,
            ]);

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => $order->paid_at ?? now(),
                'status' => $order->status === Order::STATUS_PENDING_PAYMENT ? Order::STATUS_PAID : $order->status,
            ]);

            $activeReservations = StockReservation::query()
                ->where('order_id', $order->id)
                ->where('status', 'active')
                ->count();

            $fulfilable = $order->status !== Order::STATUS_CANCELLED
                && ($activeReservations > 0 || $order->stock_committed_at !== null);

            if ($fulfilable) {
                $this->inventory->commitForOrder($order);
            } else {
                $order->update([
                    'status' => Order::STATUS_CANCELLED,
                    'admin_notes' => trim(($order->admin_notes ? $order->admin_notes."\n" : '')
                        .'Payment received after cancellation or reservation expiry. Manual review and refund or fulfilment required.'),
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => $fulfilable ? 'payment_confirmed' : 'payment_exception',
                'note' => $fulfilable
                    ? 'Payment confirmed via '.str_replace('_', ' ', $locked->provider).'.'
                    : 'Payment confirmed via '.str_replace('_', ' ', $locked->provider).', but the order was cancelled or its stock reservation had expired. Manual review is required; stock was not committed.',
                'user_id' => null,
                'created_at' => now(),
            ]);

            DB::table('product_reviews')
                ->whereIn('product_id', DB::table('order_items')->where('order_id', $order->id)->whereNotNull('product_id')->pluck('product_id'))
                ->where('author_email', $order->customer_email)
                ->where('status', ProductReview::STATUS_APPROVED)
                ->update(['verified_purchase' => true]);

            return $fulfilable ? 'normal' : 'exception';
        });

        if ($outcome === 'normal') {
            $payment->refresh()->loadMissing('order.items');
            Mail::to($payment->order->customer_email)->queue(new PaymentReceived($payment->order));
        }
    }

    public function markFailed(Payment $payment, string $reason, array $payload = []): void
    {
        DB::transaction(function () use ($payment, $reason, $payload) {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Payment::STATUS_SUCCEEDED) {
                return;
            }

            $locked->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => $reason,
                'response_payload' => array_merge($locked->response_payload ?? [], $payload),
                'completed_at' => now(),
            ]);

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->first();

            if ($order && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'failed']);

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'status' => 'payment_failed',
                    'note' => $reason,
                    'user_id' => null,
                    'created_at' => now(),
                ]);
            }
        });
    }
}
