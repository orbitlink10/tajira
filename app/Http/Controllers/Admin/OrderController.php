<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\Payments\PaymentManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('orders.view');

        $orders = Order::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('payment'), fn ($q) => $q->where('payment_status', $request->query('payment')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $q->where(fn ($inner) => $inner->where('number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_email', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'revenue' => Order::query()->where('payment_status', 'paid')->sum('total_minor'),
            'unpaid' => Order::query()->whereIn('payment_status', ['pending', 'failed'])->where('status', '!=', 'cancelled')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'summary'));
    }

    public function show(Order $order)
    {
        $this->authorize('orders.view');
        $order->load(['items', 'payments.events', 'history.user', 'reservations', 'user']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order, AuditLogger $audit, InventoryService $inventory)
    {
        $this->authorize('orders.manage');

        $data = $request->validate([
            'status' => ['required', Rule::in(['processing', 'shipped', 'completed', 'cancelled'])],
            'note' => ['nullable', 'string', 'max:500'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'restock' => ['nullable', 'boolean'],
        ]);

        if ($order->status === 'cancelled') {
            return back()->with('error', 'This order is already cancelled.');
        }

        // The cancel method below performs its own validation and authorisation.

        if ($data['status'] === 'cancelled') {
            $this->cancel($request, $order, $audit, $inventory);

            return back()->with('success', 'Order cancelled.');
        }

        if (! $order->isPaid() && ! in_array($data['status'], ['processing'], true)) {
            return back()->with('error', 'Confirm payment before moving this order to shipping or completion.');
        }

        $allowed = [
            'paid' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['completed'],
            'completed' => [],
        ];

        if (! in_array($data['status'], $allowed[$order->status] ?? [], true)) {
            return back()->with('error', 'That status transition is not allowed from '.$order->status.'.');
        }

        DB::transaction(function () use ($order, $data, $request, $audit) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $old = $locked->toArray();
            $locked->update([
                'status' => $data['status'],
                'tracking_number' => $data['tracking_number'] ?? $locked->tracking_number,
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $locked->id,
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'user_id' => $request->user()->id,
                'created_at' => now(),
            ]);

            $audit->log('order.status.updated', $locked, $old, $locked->fresh()->toArray());
        });

        return back()->with('success', 'Order status updated.');
    }

    public function cancel(Request $request, Order $order, ?AuditLogger $audit = null, ?InventoryService $inventory = null)
    {
        $this->authorize('orders.manage');
        $audit ??= app(AuditLogger::class);
        $inventory ??= app(InventoryService::class);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'restock' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($order, $data, $request, $audit, $inventory) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                return;
            }

            $old = $locked->toArray();

            if ($locked->stock_committed_at === null) {
                $inventory->releaseForOrder($locked, 'released');
            } elseif ($request->boolean('restock')) {
                foreach ($locked->items as $item) {
                    if ($item->product_id) {
                        $inventory->adjust(
                            Product::query()->findOrFail($item->product_id),
                            $item->quantity,
                            'Restock from cancelled order '.$locked->number,
                            $request->user()
                        );
                    }
                }
            }

            $locked->update([
                'status' => Order::STATUS_CANCELLED,
                'payment_status' => $locked->payment_status === 'paid' ? $locked->payment_status : 'cancelled',
                'cancelled_at' => now(),
            ]);

            $locked->payments()->whereIn('status', ['pending', 'initiated'])->update([
                'status' => 'cancelled',
                'failure_reason' => 'Order cancelled by administrator.',
                'completed_at' => now(),
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $locked->id,
                'status' => Order::STATUS_CANCELLED,
                'note' => $data['note'] ?? 'Cancelled by administrator. Refunds, if applicable, are handled separately.',
                'user_id' => $request->user()->id,
                'created_at' => now(),
            ]);

            $audit->log('order.cancelled', $locked, $old, $locked->fresh()->toArray());
        });

        return back()->with('success', 'Order cancelled. Refunds, if any, must be processed separately.');
    }

    public function manualPayment(Request $request, Order $order, PaymentManager $payments, AuditLogger $audit)
    {
        $this->authorize('orders.manage');

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['required', 'string', 'max:500'],
        ]);

        try {
            $payments->manualConfirm($order, $request->user(), $data['note'], $data['reference'] ?? null);
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        }

        $audit->log('order.manual-payment.confirmed', $order, [], ['reference' => $data['reference'] ?? null, 'note' => $data['note']]);

        return back()->with('success', 'Payment recorded. Verify the source transaction against your bank or M-Pesa records.');
    }

    public function invoice(Order $order)
    {
        $this->authorize('orders.view');
        $order->load('items');

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'business' => [
                'name' => setting('business.name', 'Tajira'),
                'legal_name' => setting('business.legal_name'),
                'address' => setting('business.address_line'),
                'city' => setting('business.city'),
                'postal_code' => setting('business.postal_code'),
                'phone' => setting('business.phone'),
                'email' => setting('business.email'),
                'tax_pin' => setting('business.tax_pin'),
                'domain' => setting('business.domain'),
            ],
        ])->setPaper('a4');

        return $pdf->download('Tajira-invoice-'.$order->number.'.pdf');
    }
}
