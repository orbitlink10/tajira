@if(session('analytics_add_to_cart'))
    @php($item = session('analytics_add_to_cart'))
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'add_to_cart',
            ecommerce: {
                currency: '{{ config('tajira.currency', 'KES') }}',
                value: {{ ((float) ($item['price'] ?? 0)) * (int) ($item['quantity'] ?? 1) }},
                items: [{
                    item_id: @json($item['item_id']),
                    item_name: @json($item['item_name']),
                    item_brand: @json($item['item_brand']),
                    item_category: @json($item['item_category']),
                    price: {{ (float) ($item['price'] ?? 0) }},
                    quantity: {{ (int) ($item['quantity'] ?? 1) }}
                }]
            }
        });
    </script>
@endif

@if(session('analytics_quotation_request'))
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'quotation_request',
            item_count: {{ (int) session('analytics_quotation_request.item_count', 0) }},
            has_products: @json((bool) session('analytics_quotation_request.has_products', false))
        });
    </script>
@endif
