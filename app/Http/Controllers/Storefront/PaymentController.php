<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function show(Order $order, PaymentManager $payments)
    {
        if ($order->isPaid()) {
            return redirect()->route('orders.show', $order->number)->with('success', 'This order is already paid.');
        }

        $order->load('items', 'payments');
        $methods = $payments->enabledMethods();

        if ($methods === []) {
            $methods = ['manual' => 'Arrange payment with our team'];
        }
        $bank = [
            'name' => setting('payments.bank_name'),
            'account_name' => setting('payments.bank_account_name'),
            'account_number' => setting('payments.bank_account_number'),
            'branch' => setting('payments.bank_branch'),
        ];
        $mpesa = config('tajira.payments.mpesa');
        $meta = $this->privateMeta('Pay for order '.$order->number, route('payments.show', $order->number));

        return view('storefront.orders.payment', compact('order', 'methods', 'bank', 'mpesa', 'meta'));
    }

    public function pay(Request $request, Order $order, PaymentManager $payments)
    {
        $enabled = array_keys($payments->enabledMethods());

        if ($enabled === []) {
            $enabled = ['manual'];
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', Rule::in($enabled)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if ($data['payment_method'] === 'manual') {
            return redirect()->route('orders.show', $order->number)
                ->with('info', 'Our team will contact you with payment instructions.');
        }

        if ($data['payment_method'] === 'mpesa' && empty($data['phone'])) {
            throw ValidationException::withMessages(['phone' => 'Enter the M-Pesa phone number.']);
        }

        try {
            $result = $payments->initiate($order, $data['payment_method'], ['phone' => $data['phone'] ?? $order->customer_phone]);
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        } catch (\Throwable $exception) {
            Log::error('Payment initiation failed', ['order' => $order->number, 'error' => $exception->getMessage()]);

            return back()->with('error', 'We could not start the payment. Please try again or contact support.');
        }

        return redirect($result['redirect'] ?? route('payments.show', $order->number))
            ->with('success', $result['message']);
    }

    public function status(Order $order)
    {
        $payment = $order->payments()->latest()->first();

        return response()->json([
            'order_status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_state' => $payment?->status,
            'paid' => $order->isPaid(),
        ])->header('Cache-Control', 'no-store');
    }

    public function mpesaCallback(Request $request, PaymentManager $payments)
    {
        $payload = $request->json()->all();

        if (! is_array($payload) || empty($payload)) {
            $payload = $request->all();
        }

        $allowedIps = config('tajira.payments.mpesa.callback_ips', []);

        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            Log::warning('M-Pesa callback rejected by IP allowlist', ['ip' => $request->ip()]);

            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Rejected'], 403);
        }

        Log::info('M-Pesa callback received', ['ip' => $request->ip()]);

        try {
            $result = $payments->handleMpesaCallback($payload, $request->ip());
        } catch (\Throwable $exception) {
            Log::error('M-Pesa callback processing error', ['error' => $exception->getMessage()]);

            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Accepted for reconciliation']);
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
            'status' => $result['status'] ?? 'received',
        ]);
    }

    private function privateMeta(string $title, string $canonical): array
    {
        return [
            'title' => $title.' | '.setting('business.name', 'Tajira'),
            'description' => 'Complete payment for your Tajira order.',
            'canonical' => $canonical,
            'robots' => 'noindex,nofollow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => null,
        ];
    }
}
