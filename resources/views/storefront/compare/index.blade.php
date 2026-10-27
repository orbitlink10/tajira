@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Compare', 'url' => route('compare.index')]]" />
        <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-ink-900">Compare products</h1>
                <p class="mt-1 text-sm text-ink-600">Compare up to four products from the same primary category. Only verified comparable attributes are shown.</p>
            </div>
            @if($products->isNotEmpty())
                <form method="post" action="{{ route('compare.clear') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Clear comparison</button>
                </form>
            @endif
        </div>
    </div>

    <div class="container-page py-8">
        @if($products->isEmpty())
            <div class="rounded-xl border border-dashed border-ink-300 bg-white p-10 text-center">
                <p class="text-lg font-semibold text-ink-900">Your comparison is empty.</p>
                <p class="mt-1 text-sm text-ink-600">Open a product and choose “Compare” to add it here.</p>
                <a href="{{ route('products.index') }}" class="btn btn-primary mt-4">Browse products</a>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-ink-200 bg-white">
                <table class="w-full min-w-[720px] text-sm">
                    <caption class="sr-only">Product comparison</caption>
                    <thead>
                        <tr class="border-b border-ink-200">
                            <th scope="col" class="sticky left-0 z-10 w-44 bg-white px-3 py-4 text-left text-xs font-semibold uppercase tracking-wide text-ink-500">Product</th>
                            @foreach($products as $product)
                                <th scope="col" class="min-w-[190px] border-l border-ink-100 px-4 py-4 text-left align-top">
                                    <a href="{{ route('products.show', $product->slug) }}" class="block">
                                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="120" height="120" loading="lazy" class="mx-auto h-28 w-28 object-contain">
                                        <span class="mt-2 block text-sm font-semibold leading-5 text-ink-900">{{ $product->name }}</span>
                                    </a>
                                    <span class="mt-1 block text-xs text-ink-500">{{ $product->brand?->name }}</span>
                                    <form method="post" action="{{ route('compare.remove', $product->slug) }}" class="mt-2">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900">Remove</button>
                                    </form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        <tr>
                            <th scope="row" class="sticky left-0 z-10 bg-white px-3 py-3 text-left text-xs font-semibold text-ink-600">Price</th>
                            @foreach($products as $product)
                                <td class="border-l border-ink-100 px-4 py-3 align-top">
                                    <x-price :product="$product" />
                                    <p class="mt-1 text-xs {{ $product->isAvailable() ? 'text-emerald-700' : 'text-ink-500' }}">{{ $product->stockLabel() }}</p>
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row" class="sticky left-0 z-10 bg-white px-3 py-3 text-left text-xs font-semibold text-ink-600">SKU</th>
                            @foreach($products as $product)
                                <td class="border-l border-ink-100 px-4 py-3 align-top text-xs text-ink-700">{{ $product->sku }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row" class="sticky left-0 z-10 bg-white px-3 py-3 text-left text-xs font-semibold text-ink-600">Model</th>
                            @foreach($products as $product)
                                <td class="border-l border-ink-100 px-4 py-3 align-top text-xs text-ink-700">{{ $product->model ?: '—' }}</td>
                            @endforeach
                        </tr>
                        @foreach($attributes as $attributeName => $values)
                            <tr>
                                <th scope="row" class="sticky left-0 z-10 bg-white px-3 py-3 text-left text-xs font-semibold text-ink-600">{{ $attributeName }}</th>
                                @foreach($products as $product)
                                    @php($value = $product->attributeValues->first(fn ($item) => $item->attribute?->name === $attributeName))
                                    <td class="border-l border-ink-100 px-4 py-3 align-top text-xs text-ink-700">
                                        {{ $value?->displayValue() ?? '—' }}{{ $value?->unit ? ' '.$value->unit : '' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <th scope="row" class="sticky left-0 z-10 bg-white px-3 py-3 text-left text-xs font-semibold text-ink-600">Action</th>
                            @foreach($products as $product)
                                <td class="border-l border-ink-100 px-4 py-3 align-top">
                                    @if($product->product_type === 'variant')
                                        <a href="{{ route('products.show', $product->slug) }}" class="btn btn-primary w-full px-3 py-2 text-xs">Choose options</a>
                                    @elseif($product->isPurchasable())
                                        <form method="post" action="{{ route('cart.add') }}">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-primary w-full px-3 py-2 text-xs" @disabled(!$product->isAvailable() && !$product->allow_oversell)>Add to cart</button>
                                        </form>
                                    @else
                                        <a href="{{ route('quotes.create', ['product' => $product->id]) }}" class="btn btn-secondary w-full px-3 py-2 text-xs">Request quote</a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-ink-500">Only attributes marked as comparable in the catalogue are shown. A dash means the value is not verified or not applicable.</p>
        @endif
    </div>
@endsection
