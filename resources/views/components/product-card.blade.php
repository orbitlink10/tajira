@props(['product'])

@php
    $image = $product->primaryImage;
    $imageUrl = $product->imageUrl();
    $width = $image?->width ?: 600;
    $height = $image?->height ?: 600;
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-xl border border-ink-200 bg-white transition hover:border-brand-300 hover:shadow-md">
    <a
        href="{{ route('products.show', $product->slug) }}"
        class="relative block aspect-square overflow-hidden bg-white p-4"
        data-analytics-item="{{ json_encode([
            'item_id' => $product->sku,
            'item_name' => $product->name,
            'item_brand' => $product->brand?->name,
            'item_category' => $product->primaryCategory?->name,
            'price' => ($product->effectivePriceMinor() ?? 0) / 100,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}"
        data-analytics-list="catalogue"
    >
        <img
            src="{{ $imageUrl }}"
            alt="{{ $image?->alt ?: $product->name }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.03]"
        >
        @if($product->isOnSale())
            <span class="absolute left-3 top-3 rounded-full bg-sand-400 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-ink-950">Sale</span>
        @elseif(!$product->isAvailable() && !$product->allow_oversell)
            <span class="absolute left-3 top-3 rounded-full bg-ink-900 px-2.5 py-1 text-[11px] font-semibold text-white">Out of stock</span>
        @endif
    </a>

    <div class="flex flex-1 flex-col border-t border-ink-100 p-4">
        @if($product->brand)
            <a href="{{ route('brands.show', $product->brand->slug) }}" class="text-[11px] font-bold uppercase tracking-wider text-brand-700 hover:text-brand-900">{{ $product->brand->name }}</a>
        @endif
        <h3 class="mt-1 line-clamp-2 text-sm font-semibold leading-5 text-ink-900">
            <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-700">{{ $product->name }}</a>
        </h3>
        <div class="mt-1 text-xs text-ink-500">{{ $product->model ?: $product->sku }}</div>

        <div class="mt-auto pt-3">
            <x-price :product="$product" />
            <p class="mt-1 text-xs {{ $product->isAvailable() ? 'text-emerald-700' : 'text-ink-500' }}">
                {{ $product->stockLabel() }}
            </p>

            <div class="mt-3 flex gap-2">
                @if($product->product_type === 'variant')
                    <a href="{{ route('products.show', $product->slug) }}" class="btn btn-primary flex-1 px-3 py-2 text-xs">Choose options</a>
                @elseif($product->isPurchasable())
                    <form method="post" action="{{ route('cart.add') }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn btn-primary w-full px-3 py-2 text-xs" @disabled(!$product->isAvailable() && !$product->allow_oversell)>
                            {{ $product->isAvailable() ? 'Add to cart' : 'Out of stock' }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('quotes.create', ['product' => $product->id]) }}" class="btn btn-secondary w-full px-3 py-2 text-xs">Request quote</a>
                @endif
                <a href="{{ route('products.show', $product->slug) }}" class="btn btn-secondary px-3 py-2 text-xs" aria-label="View {{ $product->name }}">View</a>
            </div>
        </div>
    </div>
</article>
