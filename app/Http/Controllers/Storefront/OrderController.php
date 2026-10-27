<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\InventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function track()
    {
        $meta = $this->privateMeta('Track your order', route('orders.track'));

        return view('storefront.orders.track', compact('meta'));
    }

    public function find(Request $request)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:180'],
        ]);

        $order = Order::query()
            ->where('number', strtoupper(trim($data['number'])))
            ->where('customer_email', strtolower(trim($data['email'])))
            ->first();

        if (! $order) {
            throw ValidationException::withMessages([
                'number' => 'We could not find an order with that number and email. Check your confirmation email.',
            ]);
        }

        $request->session()->put('order_access.'.$order->number, true);

        return redirect()->route('orders.show', $order->number);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'payments', 'history', 'reservations']);
        $meta = $this->privateMeta('Order '.$order->number, route('orders.show', $order->number));

        return view('storefront.orders.show', compact('order', 'meta'));
    }

    public function cancel(Order $order, InventoryService $inventory)
    {
        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            return back()->with('error', 'This order can no longer be cancelled online. Please contact support.');
        }

        DB::transaction(function () use ($order, $inventory) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Order::STATUS_PENDING_PAYMENT || $locked->payment_status === 'paid') {
                throw ValidationException::withMessages(['order' => 'This order has already moved past the cancellation window.']);
            }

            $inventory->releaseForOrder($locked, 'released');

            $locked->update([
                'status' => Order::STATUS_CANCELLED,
                'payment_status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $locked->payments()->whereIn('status', ['pending', 'initiated'])->update([
                'status' => 'cancelled',
                'failure_reason' => 'Order cancelled by customer.',
                'completed_at' => now(),
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $locked->id,
                'status' => Order::STATUS_CANCELLED,
                'note' => 'Order cancelled by customer.',
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('orders.show', $order->number)->with('success', 'Order cancelled and reserved stock released.');
    }

    public function invoice(Order $order)
    {
        $order->load('items');

        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order, 'business' => $this->business()])
            ->setPaper('a4');

        return $pdf->download('Tajira-invoice-'.$order->number.'.pdf');
    }

    private function business(): array
    {
        return [
            'name' => setting('business.name', 'Tajira'),
            'legal_name' => setting('business.legal_name'),
            'address' => setting('business.address_line'),
            'city' => setting('business.city'),
            'postal_code' => setting('business.postal_code'),
            'phone' => setting('business.phone'),
            'email' => setting('business.email'),
            'tax_pin' => setting('business.tax_pin'),
            'domain' => setting('business.domain'),
        ];
    }

    private function privateMeta(string $title, string $canonical): array
    {
        return [
            'title' => $title.' | '.setting('business.name', 'Tajira'),
            'description' => 'Your Tajira order.',
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
