@props(['product'])

@php
    $image = $product->primaryImage;
    $price = $product->effectivePriceMinor();
    $prices = $product->product_type === 'variant'
        ? $product->activeVariants->map(fn ($variant) => $product->effectivePriceMinor($variant))->filter(fn ($value) => $value !== null)
        : collect();
    $minimum = $prices->isNotEmpty() ? $prices->min() : $price;
    $maximum = $prices->isNotEmpty() ? $prices->max() : $price;
@endphp

<article class="tajira-brand-product">
    <a href="{{ route('products.show', $product->slug) }}" class="tajira-brand-product-image">
        <img src="{{ $product->imageUrl() }}" alt="{{ $image?->alt ?: $product->name }}" width="{{ $image?->width ?: 600 }}" height="{{ $image?->height ?: 600 }}" loading="lazy" decoding="async">
        <span class="tajira-brand-product-badges">
            @if($product->isOnSale())
                <span class="tajira-product-offer">OFFER</span>
            @endif
            @if($product->is_featured)
                <span class="tajira-product-hot">HOT</span>
            @endif
        </span>
    </a>
    <div class="tajira-brand-product-details">
        <h3><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h3>
        <a class="tajira-brand-product-brand" href="{{ route('brands.show', $product->brand->slug) }}">{{ $product->brand->name }}</a>
        <div class="tajira-brand-product-price">
            @if($minimum === null)
                <a href="{{ route('quotes.create', ['product' => $product->id]) }}">Request price</a>
            @else
                <span>{{ money($minimum) }}@if($maximum > $minimum) &ndash; {{ money($maximum) }}@endif</span>
                @if($product->isOnSale() && $product->price_minor && $minimum === $maximum)
                    <del>{{ money($product->price_minor) }}</del>
                @endif
            @endif
        </div>
    </div>
</article>
