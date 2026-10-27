@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'All products', 'url' => route('products.index')]]" />
        <div class="mt-4">
            <h1 class="text-3xl font-bold tracking-tight text-ink-900">All networking products</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">Browse published products across wireless, structured cabling, fibre, security, access control and telephony. Use the filters to narrow by brand and verified technical attributes.</p>
        </div>
    </div>

    <div class="container-page py-8">
        <div class="lg:grid lg:grid-cols-[260px_1fr] lg:gap-8">
            <aside class="hidden lg:block">
                <div class="card sticky top-4 p-4">
                    <h2 class="mb-4 text-sm font-bold text-ink-900">Filters</h2>
                    @include('storefront.products.partials.filters', ['filters' => $filters, 'filterGroups' => $filterGroups, 'filterBrands' => $filterBrands, 'prefix' => 'desktop'])
                </div>
            </aside>

            <div>
                <details class="card mb-4 p-4 lg:hidden">
                    <summary class="cursor-pointer text-sm font-bold text-ink-900">Filters and sorting</summary>
                    <div class="mt-4">
                        @include('storefront.products.partials.filters', ['filters' => $filters, 'filterGroups' => $filterGroups, 'filterBrands' => $filterBrands, 'prefix' => 'mobile'])
                    </div>
                </details>

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-ink-600">{{ number_format($products->total()) }} product{{ $products->total() === 1 ? '' : 's' }}</p>
                    <a href="{{ route('quotes.create') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Can’t find it? Request a quote →</a>
                </div>

                @include('storefront.products.partials.grid', ['products' => $products])
            </div>
        </div>
    </div>
@endsection
