@extends('layouts.admin')

@section('title', 'Order '.$order->number)
@section('heading', 'Order '.$order->number)
@section('subheading', $order->placed_at?->format('j F Y, H:i').' · '.$order->statusLabel())

@section('content')
    @if($order->status === 'cancelled' && $order->isPaid())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900">
            <strong>Payment exception:</strong> payment was confirmed after cancellation or reservation expiry. Stock was not committed. Refund or re-fulfil manually after verifying the transaction.
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-brand-700">← All orders</a>
        <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-secondary ml-auto">Invoice PDF</a>
        <a href="{{ route('orders.show', $order->number) }}" target="_blank" class="btn btn-secondary">Customer view</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr_380px]">
        <div class="space-y-4">
            <section class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-ink-200 px-5 py-4">
                    <h2 class="text-sm font-bold text-ink-900">Items</h2>
                    <div class="flex gap-2">
                        <span class="badge bg-ink-100 text-ink-700">{{ $order->statusLabel() }}</span>
                        <span class="badge {{ $order->isPaid() ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">{{ $order->paymentStatusLabel() }}</span>
                    </div>
                </div>
                <div class="divide-y divide-ink-100">
                    @foreach($order->items as $item)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-ink-900">{{ $item->name }}</p>
                                <p class="text-xs text-ink-500">SKU {{ $item->sku }} · {{ money($item->unit_price_minor) }} × {{ $item->quantity }}</p>
                                @if($item->attributes)<p class="text-xs text-ink-400">{{ collect($item->attributes)->map(fn ($v, $k) => $k.': '.$v)->implode(' · ') }}</p>@endif
                            </div>
                            <p class="text-sm font-semibold">{{ money($item->line_total_minor) }}</p>
                        </div>
                    @endforeach
                </div>
                <dl class="space-y-1.5 border-t border-ink-200 bg-ink-50 px-5 py-4 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ money($order->subtotal_minor) }}</dd></div>
                    @if($order->discount_minor > 0)<div class="flex justify-between"><dt>Discount</dt><dd>− {{ money($order->discount_minor) }}</dd></div>@endif
                    <div class="flex justify-between"><dt>Tax</dt><dd>{{ money($order->tax_minor) }}</dd></div>
                    <div class="flex justify-between"><dt>Delivery</dt><dd>{{ money($order->shipping_minor) }}</dd></div>
                    <div class="flex justify-between border-t border-ink-200 pt-2 text-base font-bold"><dt>Total</dt><dd>{{ $order->formattedTotal() }}</dd></div>
                </dl>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Customer and delivery</h2>
                <div class="mt-3 grid gap-4 text-sm sm:grid-cols-2">
                    <div class="leading-6 text-ink-700">
                        <p class="font-semibold text-ink-900">{{ $order->customer_name }}</p>
                        @if($order->company)<p>{{ $order->company }}</p>@endif
                        <p>{{ $order->customer_email }}</p>
                        <p>{{ $order->customer_phone }}</p>
                        @if($order->user)<p class="text-xs text-ink-500">Account #{{ $order->user->id }}</p>@else<p class="text-xs text-ink-500">Guest checkout</p>@endif
                    </div>
                    <div class="leading-6 text-ink-700">
                        <p class="font-semibold text-ink-900">{{ $order->delivery_type === 'pickup' ? 'Pickup' : 'Delivery' }}</p>
                        <p>{{ $order->address_line }}</p>
                        <p>{{ $order->town }}, {{ $order->county }} @if($order->postal_code) · {{ $order->postal_code }}@endif</p>
                        <p class="text-xs text-ink-500">{{ $order->shipping_method_name }} @if($order->shipping_lead_time) · {{ $order->shipping_lead_time }} @endif</p>
                        @if($order->delivery_notes)<p class="text-xs text-ink-500">Notes: {{ $order->delivery_notes }}</p>@endif
                    </div>
                </div>
                @if($order->notes)<div class="mt-3 rounded-lg bg-ink-50 p-3 text-xs text-ink-600">Customer notes: {{ $order->notes }}</div>@endif
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Payment records</h2>
                @if($order->payments->isEmpty())
                    <p class="mt-2 text-sm text-ink-500">No payment attempts recorded.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="table-admin">
                            <thead><tr><th>Provider</th><th>Status</th><th>Amount</th><th>Reference</th><th>Initiated</th><th>Completed</th></tr></thead>
                            <tbody>
                                @foreach($order->payments->sortByDesc('id') as $payment)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $payment->provider)) }}</td>
                                        <td><span class="badge {{ $payment->status === 'succeeded' ? 'bg-emerald-50 text-emerald-800' : ($payment->status === 'failed' ? 'bg-red-50 text-red-800' : 'bg-ink-100 text-ink-700') }}">{{ ucfirst($payment->status) }}</span></td>
                                        <td>{{ $payment->formattedAmount() }}</td>
                                        <td class="max-w-[180px] truncate text-xs">{{ $payment->provider_reference ?: $payment->merchant_reference ?: '—' }}</td>
                                        <td class="text-xs">{{ $payment->initiated_at?->format('j M, H:i') ?: '—' }}</td>
                                        <td class="text-xs">{{ $payment->completed_at?->format('j M, H:i') ?: '—' }}</td>
                                    </tr>
                                    @if($payment->failure_reason)
                                        <tr><td colspan="6" class="text-xs text-red-700">{{ $payment->failure_reason }}</td></tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">History</h2>
                <ol class="mt-3 space-y-3">
                    @foreach($order->history as $entry)
                        <li class="flex gap-3 text-sm">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500" aria-hidden="true"></span>
                            <div>
                                <p class="font-medium text-ink-900">{{ ucwords(str_replace('_', ' ', $entry->status)) }} <span class="text-xs font-normal text-ink-400">{{ $entry->created_at?->format('j M Y, H:i') }}</span></p>
                                @if($entry->note)<p class="text-xs text-ink-600">{{ $entry->note }}</p>@endif
                                @if($entry->user)<p class="text-xs text-ink-400">by {{ $entry->user->name }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-4">
            @can('orders.manage')
                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Update status</h2>
                    <form method="post" action="{{ route('admin.orders.status', $order) }}" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label for="status" class="field-label">New status</label>
                            <select id="status" name="status" class="field-input">
                                @foreach(['processing' => 'Processing', 'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label for="tracking_number" class="field-label">Tracking number</label><input id="tracking_number" name="tracking_number" value="{{ $order->tracking_number }}" class="field-input"></div>
                        <div><label for="note" class="field-label">Note (visible internally)</label><textarea id="note" name="note" rows="2" class="field-input"></textarea></div>
                        <label class="flex items-center gap-2 text-xs text-ink-600"><input type="checkbox" name="restock" value="1" class="h-4 w-4 rounded"> Restock items if cancelling a paid order</label>
                        <button class="btn btn-primary w-full">Save status</button>
                    </form>
                </section>

                @if(!$order->isPaid())
                    <section class="card p-5">
                        <h2 class="text-sm font-bold text-ink-900">Record manual payment</h2>
                        <p class="mt-1 text-xs text-ink-500">Use only after independently verifying the bank or M-Pesa credit. This commits stock and marks the order paid.</p>
                        <form method="post" action="{{ route('admin.orders.manual-payment', $order) }}" class="mt-3 space-y-3" onsubmit="return confirm('Confirm this payment and commit stock?');">
                            @csrf
                            <div><label for="reference" class="field-label">Transaction reference</label><input id="reference" name="reference" class="field-input"></div>
                            <div><label for="payment_note" class="field-label">Verification note</label><textarea id="payment_note" name="note" rows="2" required class="field-input" placeholder="Verified against bank statement / M-Pesa receipt"></textarea></div>
                            <button class="btn btn-accent w-full">Mark as paid</button>
                        </form>
                    </section>
                @endif

                @if($order->status !== 'cancelled')
                    <section class="card p-5">
                        <h2 class="text-sm font-bold text-ink-900">Cancel order</h2>
                        <p class="mt-1 text-xs text-ink-500">Cancelling releases active reservations. Paid orders require a separate refund decision.</p>
                        <form method="post" action="{{ route('admin.orders.cancel', $order) }}" class="mt-3" onsubmit="return confirm('Cancel this order?');">
                            @csrf
                            <input type="hidden" name="note" value="Cancelled by administrator from order screen.">
                            <label class="flex items-center gap-2 text-xs text-ink-600"><input type="checkbox" name="restock" value="1" class="h-4 w-4 rounded"> Restock committed items</label>
                            <button class="btn btn-secondary mt-2 w-full text-red-700">Cancel order</button>
                        </form>
                    </section>
                @endif
            @endcan

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Reservations</h2>
                @forelse($order->reservations as $reservation)
                    <p class="mt-2 text-xs text-ink-600">{{ $reservation->quantity }} × product #{{ $reservation->product_id }} · {{ $reservation->status }} · expires {{ $reservation->expires_at?->format('j M, H:i') }}</p>
                @empty
                    <p class="mt-2 text-sm text-ink-500">No reservations.</p>
                @endforelse
            </section>
        </aside>
    </div>
@endsection
