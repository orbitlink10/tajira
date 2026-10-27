@props(['products', 'emptyMessage' => 'No products matched these filters.'])

@if($products->isEmpty())
    <div class="rounded-xl border border-dashed border-ink-300 bg-white p-10 text-center">
        <p class="text-base font-semibold text-ink-900">{{ $emptyMessage }}</p>
        <p class="mt-1 text-sm text-ink-600">Try removing a filter, checking the model number, or browsing all products.</p>
        <div class="mt-4 flex flex-wrap justify-center gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-primary">Browse all products</a>
            <a href="{{ route('quotes.create') }}" class="btn btn-secondary">Ask for a quotation</a>
        </div>
    </div>
@else
    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3">
        @foreach($products as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>

    @push('scripts')
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'view_item_list',
                ecommerce: {
                    currency: '{{ config('tajira.currency', 'KES') }}',
                    item_list_name: 'catalogue',
                    items: [
                        @foreach($products->items() as $product)
                            {
                                item_id: @json($product->sku),
                                item_name: @json($product->name),
                                item_brand: @json($product->brand?->name),
                                item_category: @json($product->primaryCategory?->name),
                                price: {{ ($product->effectivePriceMinor() ?? 0) / 100 }}
                            }{{ !$loop->last ? ',' : '' }}
                        @endforeach
                    ]
                }
            });
        </script>
    @endpush
@endif
