@props(['product', 'variant' => null, 'size' => 'base'])

@php
    $price = $product->effectivePriceMinor($variant);
    $list = $variant?->price_minor ?? $product->price_minor;
    $onSale = $variant && $variant->sale_price_minor !== null
        ? $variant->sale_price_minor < ($variant->price_minor ?? PHP_INT_MAX)
        : $product->isOnSale();
    $inclusive = $product->taxInclusive();
@endphp

@if($price === null)
    <div>
        <p class="{{ $size === 'lg' ? 'text-2xl' : 'text-base' }} font-bold text-ink-900">Request price</p>
        <p class="mt-1 text-xs text-ink-500">Priced by quotation for this item.</p>
    </div>
@else
    <div>
        <div class="flex flex-wrap items-baseline gap-2">
            <span class="{{ $size === 'lg' ? 'text-3xl' : 'text-base' }} font-bold tracking-tight text-ink-900">{{ money($price) }}</span>
            @if($onSale && $list)
                <span class="text-sm text-ink-400 line-through">{{ money($list) }}</span>
                <span class="badge bg-sand-200 text-ink-900">Sale</span>
            @endif
        </div>
        <p class="mt-0.5 text-xs text-ink-500">{{ $inclusive ? 'Price includes tax' : 'Price excludes tax' }} · {{ config('tajira.currency', 'KES') }}</p>
    </div>
@endif
