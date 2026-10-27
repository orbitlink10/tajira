@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Orders', 'url' => route('orders.track')], ['name' => $order->number, 'url' => route('orders.show', $order->number)]]" />

        <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Order</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-ink-900">{{ $order->number }}</h1>
                <p class="mt-1 text-sm text-ink-500">Placed {{ $order->placed_at?->format('j F Y, H:i') }} · {{ $order->items->sum('quantity') }} item(s)</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('orders.invoice', $order->number) }}" class="btn btn-secondary">Download invoice</a>
                <a href="{{ route('products.index') }}" class="btn btn-primary">Continue shopping</a>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            <span class="badge {{ $order->status === 'cancelled' ? 'bg-red-50 text-red-800' : ($order->isPaid() ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900') }}">Order: {{ $order->statusLabel() }}</span>
            <span class="badge {{ $order->isPaid() ? 'bg-emerald-50 text-emerald-800' : 'bg-ink-100 text-ink-700' }}">Payment: {{ $order->paymentStatusLabel() }}</span>
            @if($order->delivery_type === 'pickup')
                <span class="badge bg-sky-50 text-sky-800">Pickup</span>
            @endif
        </div>
    </div>

    <div class="container-page py-8">
        @if(!$order->isPaid() && in_array($order->status, ['pending_payment', 'paid'], true))
            <div class="mb-5 flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold text-amber-950">Payment is still pending.</p>
                    <p class="mt-1 text-sm text-amber-900">Stock has been reserved temporarily. Complete payment to confirm the order; unpaid reservations are released after the configured window.</p>
                </div>
                <a href="{{ route('payments.show', $order->number) }}" class="btn btn-accent shrink-0">Complete payment</a>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-5">
                <section class="card overflow-hidden">
                    <h2 class="border-b border-ink-200 px-5 py-4 text-base font-bold text-ink-900">Items</h2>
                    <div class="divide-y divide-ink-100">
                        @foreach($order->items as $item)
                            <div class="flex gap-4 p-5">
                                @if($item->image_path)
                                    <img src="{{ asset('storage/'.$item->image_path) }}" alt="" width="64" height="64" loading="lazy" class="h-16 w-16 rounded-lg border border-ink-100 object-contain">
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink-900">{{ $item->name }}</p>
                                    <p class="mt-0.5 text-xs text-ink-500">SKU {{ $item->sku }}{{ $item->brand_name ? ' · '.$item->brand_name : '' }}</p>
                                    <p class="mt-1 text-xs text-ink-600">{{ money($item->unit_price_minor) }} × {{ $item->quantity }}</p>
                                </div>
                                <p class="text-sm font-semibold text-ink-900">{{ money($item->line_total_minor) }}</p>
                            </div>
                        @endforeach
                    </div>
                    <dl class="space-y-2 border-t border-ink-200 bg-ink-50 px-5 py-4 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-600">Subtotal</dt><dd>{{ money($order->subtotal_minor) }}</dd></div>
                        @if($order->discount_minor > 0)
                            <div class="flex justify-between"><dt class="text-ink-600">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt><dd class="text-emerald-700">− {{ money($order->discount_minor) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-ink-600">{{ $order->tax_inclusive ? 'Tax included' : 'Tax' }}</dt><dd>{{ money($order->tax_minor) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-600">Delivery — {{ $order->shipping_method_name ?: 'Not specified' }}</dt><dd>{{ $order->shipping_minor === 0 ? 'Free' : money($order->shipping_minor) }}</dd></div>
                        <div class="flex justify-between border-t border-ink-200 pt-2 text-base font-bold"><dt>Total</dt><dd>{{ $order->formattedTotal() }}</dd></div>
                    </dl>
                </section>

                <section class="card p-5">
                    <h2 class="text-base font-bold text-ink-900">Order history</h2>
                    <ol class="mt-4 space-y-4">
                        @forelse($order->history as $entry)
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $loop->first ? 'bg-brand-600' : 'bg-ink-300' }}" aria-hidden="true"></span>
                                <div>
                                    <p class="text-sm font-semibold text-ink-900">{{ ucwords(str_replace('_', ' ', $entry->status)) }}</p>
                                    @if($entry->note)<p class="text-xs text-ink-600">{{ $entry->note }}</p>@endif
                                    <p class="mt-0.5 text-xs text-ink-400">{{ $entry->created_at?->format('j M Y, H:i') }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-ink-500">Status updates will appear here.</li>
                        @endforelse
                    </ol>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Delivery details</h2>
                    <address class="mt-3 not-italic text-sm leading-6 text-ink-700">
                        <strong class="block text-ink-900">{{ $order->customer_name }}</strong>
                        {{ $order->customer_phone }}<br>
                        {{ $order->customer_email }}<br>
                        @if($order->company){{ $order->company }}<br>@endif
                        {{ $order->address_line }}<br>
                        {{ $order->town }}, {{ $order->county }}
                        @if($order->postal_code)<br>{{ $order->postal_code }}@endif
                    </address>
                    @if($order->delivery_notes)
                        <p class="mt-2 text-xs text-ink-500">Notes: {{ $order->delivery_notes }}</p>
                    @endif
                    @if($order->shipping_lead_time)
                        <p class="mt-2 text-xs text-ink-500">Lead time: {{ $order->shipping_lead_time }}</p>
                    @endif
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Need help?</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">Quote your order number when contacting us.</p>
                    <a href="{{ route('contact') }}" class="btn btn-secondary mt-3 w-full">Contact support</a>
                    @if(whatsapp_url())
                        <a href="{{ whatsapp_url('Hello Tajira, I need help with order '.$order->number.'.') }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary mt-2 w-full">WhatsApp</a>
                    @endif
                </section>

                @if($order->status === 'pending_payment')
                    <section class="card p-5">
                        <h2 class="text-sm font-bold text-ink-900">Cancel this order</h2>
                        <p class="mt-2 text-xs text-ink-600">Cancelling releases any reserved stock. Paid orders must be handled through our returns process.</p>
                        <form method="post" action="{{ route('orders.cancel', $order->number) }}" class="mt-3" onsubmit="return confirm('Cancel this order?');">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full text-red-700">Cancel order</button>
                        </form>
                    </section>
                @endif
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    @if($order->isPaid())
        <script>
            (function () {
                var key = 'tajira_purchase_{{ $order->number }}';
                if (localStorage.getItem(key)) return;
                localStorage.setItem(key, '1');
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    event: 'purchase',
                    ecommerce: {
                        transaction_id: @json($order->number),
                        currency: @json($order->currency),
                        value: {{ $order->total_minor / 100 }},
                        tax: {{ $order->tax_minor / 100 }},
                        shipping: {{ $order->shipping_minor / 100 }},
                        items: [
                            @foreach($order->items as $item)
                                {
                                    item_id: @json($item->sku),
                                    item_name: @json($item->name),
                                    item_brand: @json($item->brand_name),
                                    price: {{ $item->unit_price_minor / 100 }},
                                    quantity: {{ $item->quantity }}
                                }{{ !$loop->last ? ',' : '' }}
                            @endforeach
                        ]
                    }
                });
            })();
        </script>
    @endif
@endpush
