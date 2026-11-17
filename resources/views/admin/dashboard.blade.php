@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Administration')
@section('subheading', 'Sales, stock and content signals across Tajira.')

@section('content')

    <section class="tajira-hero">
        <div><span class="tajira-pill">Admin Overview</span><h2>Dashboard</h2><p>View and manage customer orders, products, quotations and store content.</p></div>
        <div class="tajira-actions">
            @can('catalogue.manage')<a class="primary" href="{{ route('admin.products.create') }}">+ New Product</a>@endcan
            @can('catalogue.view')<a href="{{ route('admin.products.index') }}">Manage Products</a>@endcan
            @can('reviews.moderate')<a href="{{ route('admin.reviews.index') }}">Manage Reviews</a>@endcan
        </div>
    </section>
    <div class="tajira-breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Dashboard</span></div>
    <section class="tajira-overview">
        @foreach([
            ['OR', 'Orders', $totalOrders, $pendingPayment.' awaiting payment', 'admin.orders.index', 'blue'],
            ['PR', 'Products', array_sum($productCounts), ($productCounts['published'] ?? 0).' published listings', 'admin.products.index', 'teal'],
            ['QT', 'Quotations', $newQuotations, 'New quotation requests', 'admin.quotations.index', 'dark'],
            ['RV', 'Reviews', $pendingReviews, 'Awaiting moderation', 'admin.reviews.index', 'rose'],
        ] as [$icon, $label, $value, $description, $route, $tone])
            <article class="tajira-stat {{ $tone }}"><span class="tajira-stat-icon">{{ $icon }}</span><p>{{ $label }}</p><h3>{{ number_format($value) }}</h3><small>{{ $description }}</small><a href="{{ route($route) }}">View {{ strtolower($label) }} <span aria-hidden="true">&rsaquo;</span></a></article>
        @endforeach
    </section>
    <section class="tajira-summary">
        @foreach([
            ['Paid sales - 30 days', money((int) $revenue30), 'Confirmed payments', 'dark'],
            ['Paid sales today', money((int) $revenueToday), $ordersToday.' orders placed today', 'blue'],
            ['New messages', $newMessages, 'Customer enquiries', 'rose'],
            ['Low or out of stock', $lowStock, 'Products needing attention', 'teal'],
        ] as [$label, $value, $description, $tone])
            <article class="tajira-summary-card {{ $tone }}"><p>{{ $label }}</p><h3>{{ $value }}</h3><small>{{ $description }}</small></article>
        @endforeach
    </section>
    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-ink-200 px-4 py-3">
                <h2 class="text-sm font-bold text-ink-900">Recent orders</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">All orders</a>
            </div>
            @if($recentOrders->isEmpty())
                <p class="p-5 text-sm text-ink-500">No orders yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table-admin">
                        <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr></thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                                <tr>
                                    <td class="font-semibold text-ink-900">{{ $order->number }}<span class="block text-xs font-normal text-ink-400">{{ $order->created_at->format('j M, H:i') }}</span></td>
                                    <td>{{ $order->customer_name }}<span class="block text-xs text-ink-400">{{ $order->county }}</span></td>
                                    <td><span class="badge bg-ink-100 text-ink-700">{{ $order->statusLabel() }}</span></td>
                                    <td><span class="badge {{ $order->isPaid() ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">{{ $order->paymentStatusLabel() }}</span></td>
                                    <td class="whitespace-nowrap">{{ $order->formattedTotal() }}</td>
                                    <td><a href="{{ route('admin.orders.show', $order->number) }}" class="text-xs font-semibold text-brand-700">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-ink-200 px-4 py-3"><h2 class="text-sm font-bold text-ink-900">Admin Products</h2><a class="text-xs font-semibold text-brand-700" href="{{ route('admin.products.index') }}">Latest listings</a></div>
            <div class="overflow-x-auto"><table class="table-admin"><thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead><tbody>
                @forelse($recentProducts as $product)
                    <tr><td><a class="font-semibold" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a></td><td>{{ $product->primaryCategory?->name ?? 'Uncategorised' }}</td><td class="whitespace-nowrap">{{ $product->formattedPrice() }}</td><td>{{ $product->availableStock() }}</td><td><span class="badge bg-ink-100 text-ink-700">{{ ucwords(str_replace('_', ' ', $product->status)) }}</span></td></tr>
                @empty
                    <tr><td colspan="5" class="text-ink-500">No products yet.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-ink-200 px-4 py-3">
                <h2 class="text-sm font-bold text-ink-900">Recent quotations</h2>
                <a href="{{ route('admin.quotations.index') }}" class="text-xs font-semibold text-brand-700">View all</a>
            </div>
            @if($recentQuotations->isEmpty())
                <p class="p-5 text-sm text-ink-500">No quotation requests yet.</p>
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach($recentQuotations as $quotation)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div>
                                <a href="{{ route('admin.quotations.show', $quotation->number) }}" class="text-sm font-semibold text-ink-900 hover:text-brand-700">{{ $quotation->number }}</a>
                                <p class="text-xs text-ink-500">{{ $quotation->name }} · {{ $quotation->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="badge bg-ink-100 text-ink-700">{{ $quotation->statusLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card p-4">
            <h2 class="text-sm font-bold text-ink-900">Import and SEO attention</h2>
            @if($activeImports->isEmpty())
                <p class="mt-2 text-sm text-ink-500">No import batches are currently running.</p>
            @else
                <ul class="mt-2 space-y-2 text-sm">
                    @foreach($activeImports as $batch)
                        <li>
                            <a href="{{ route('admin.imports.show', $batch) }}" class="font-medium text-brand-700 hover:text-brand-900">{{ $batch->reference }}</a>
                            <span class="text-xs text-ink-500">{{ $batch->statusLabel() }} · {{ $batch->processed_rows }}/{{ $batch->total_rows }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <a href="{{ route('admin.seo.index') }}" class="rounded-lg border border-ink-200 p-3 hover:border-brand-300"><span class="block text-xl font-bold">{{ $seo['missing_meta_description'] }}</span><span class="text-xs text-ink-500">Missing meta descriptions</span></a>
                <a href="{{ route('admin.seo.index') }}" class="rounded-lg border border-ink-200 p-3 hover:border-brand-300"><span class="block text-xl font-bold">{{ $seo['orphan_products'] }}</span><span class="text-xs text-ink-500">Orphan products</span></a>
                <a href="{{ route('admin.seo.index') }}" class="rounded-lg border border-ink-200 p-3 hover:border-brand-300"><span class="block text-xl font-bold">{{ $seo['missing_images'] }}</span><span class="text-xs text-ink-500">Missing images</span></a>
                <a href="{{ route('admin.seo.index') }}" class="rounded-lg border border-ink-200 p-3 hover:border-brand-300"><span class="block text-xl font-bold">{{ $seo['broken_internal_links'] }}</span><span class="text-xs text-ink-500">Broken links</span></a>
            </div>
            <p class="mt-3 text-xs text-ink-500">SEO checks are diagnostics, not guarantees of ranking or rich results.</p>
        </section>
    </div>
@endsection
