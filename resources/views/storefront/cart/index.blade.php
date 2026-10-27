@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Cart', 'url' => route('cart.index')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900">Your cart</h1>
        <p class="mt-2 text-sm text-ink-600">Review quantities and options before checkout. Stock is only reserved when you place the order.</p>
    </div>

    <div class="container-page py-8">
        @if($items->isEmpty())
            <div class="rounded-xl border border-dashed border-ink-300 bg-white p-10 text-center">
                <p class="text-lg font-semibold text-ink-900">Your cart is empty.</p>
                <p class="mt-1 text-sm text-ink-600">Browse the catalogue or send us a list for quotation.</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('products.index') }}" class="btn btn-primary">Browse products</a>
                    <a href="{{ route('quotes.create') }}" class="btn btn-secondary">Request a quotation</a>
                </div>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="space-y-3">
                    @foreach($items as $item)
                        @php
                            $product = $item['product'];
                            $variant = $item['variant'];
                            $overStock = !$product->allow_oversell && $item['quantity'] > $item['available'];
                        @endphp
                        <div class="card flex flex-col gap-4 p-4 sm:flex-row">
                            <a href="{{ route('products.show', $product->slug) }}" class="shrink-0">
                                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="112" height="112" loading="lazy" class="h-24 w-24 rounded-lg border border-ink-100 bg-white object-contain p-2 sm:h-28 sm:w-28">
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        @if($product->brand)<p class="text-[11px] font-bold uppercase tracking-wider text-brand-700">{{ $product->brand->name }}</p>@endif
                                        <h2 class="text-sm font-semibold text-ink-900"><a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-700">{{ $product->name }}</a></h2>
                                        <p class="mt-0.5 text-xs text-ink-500">SKU {{ $variant?->sku ?? $product->sku }} @if($variant) · {{ $variant->name }} @endif</p>
                                    </div>
                                    <p class="text-sm font-bold text-ink-900">{{ money($item['line_total_minor']) }}</p>
                                </div>

                                <p class="mt-1 text-xs text-ink-500">{{ money($item['unit_price_minor']) }} each</p>

                                @if($overStock)
                                    <p class="mt-2 text-xs font-medium text-amber-800">Only {{ $item['available'] }} available. Reduce the quantity or request a quote.</p>
                                @endif

                                <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
                                    <form method="post" action="{{ route('cart.update') }}" class="flex items-end gap-2">
                                        @csrf
                                        <div>
                                            <label for="qty-{{ $item['key'] }}" class="sr-only">Quantity for {{ $product->name }}</label>
                                            <input id="qty-{{ $item['key'] }}" type="number" name="items[{{ $item['key'] }}]" value="{{ $item['quantity'] }}" min="0" max="99" class="field-input w-24 py-1.5">
                                        </div>
                                        <button type="submit" class="btn btn-secondary px-3 py-1.5 text-xs">Update</button>
                                    </form>

                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('quotes.create', ['product' => $product->id]) }}" class="text-xs font-medium text-brand-700 hover:text-brand-900">Quote this item</a>
                                        <form method="post" action="{{ route('cart.remove') }}">
                                            @csrf
                                            <input type="hidden" name="key" value="{{ $item['key'] }}">
                                            <button type="submit" class="text-xs font-medium text-red-700 hover:text-red-900">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <a href="{{ route('products.index') }}" class="inline-block text-sm font-semibold text-brand-700 hover:text-brand-900">← Continue shopping</a>
                </div>

                <aside class="space-y-4">
                    <div class="card p-5">
                        <h2 class="text-base font-bold text-ink-900">Order summary</h2>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-ink-600">Subtotal</dt><dd class="font-medium text-ink-900">{{ money($totals['subtotal']) }}</dd></div>
                            @if($totals['discount'] > 0)
                                <div class="flex justify-between"><dt class="text-ink-600">Coupon ({{ $coupon?->code }})</dt><dd class="font-medium text-emerald-700">− {{ money($totals['discount']) }}</dd></div>
                            @endif
                            <div class="flex justify-between"><dt class="text-ink-600">{{ $totals['lines'] && collect($totals['lines'])->every(fn ($line) => $line['tax_inclusive']) ? 'Tax included' : 'Tax' }}</dt><dd class="font-medium text-ink-900">{{ money($totals['tax']) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-ink-600">Delivery</dt><dd class="text-ink-600">Calculated at checkout</dd></div>
                            <div class="border-t border-ink-200 pt-2 flex justify-between text-base"><dt class="font-bold text-ink-900">Estimated total</dt><dd class="font-bold text-ink-900">{{ money($totals['total']) }}</dd></div>
                        </dl>
                        <p class="mt-2 text-xs text-ink-500">The total excludes delivery until a method is selected. Stock and final totals are verified when the order is placed.</p>
                        <a href="{{ route('checkout.index') }}" class="btn btn-primary mt-4 w-full py-3">Proceed to checkout</a>
                        <a href="{{ route('quotes.create') }}" class="btn btn-secondary mt-2 w-full">Request a quotation instead</a>
                    </div>

                    <div class="card p-5">
                        <h2 class="text-sm font-bold text-ink-900">Coupon</h2>
                        @if($coupon)
                            <p class="mt-2 text-sm text-emerald-800">Applied: <strong>{{ $coupon->code }}</strong> ({{ $coupon->displayValue() }} off)</p>
                            <form method="post" action="{{ route('cart.coupon.remove') }}" class="mt-2">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900">Remove coupon</button>
                            </form>
                        @else
                            <form method="post" action="{{ route('cart.coupon.apply') }}" class="mt-2 flex gap-2">
                                @csrf
                                <label for="coupon_code" class="sr-only">Coupon code</label>
                                <input id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}" class="field-input" placeholder="Coupon code">
                                <button type="submit" class="btn btn-secondary px-3">Apply</button>
                            </form>
                        @endif
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endsection
