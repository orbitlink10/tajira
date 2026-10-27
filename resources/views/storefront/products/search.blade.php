@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Search', 'url' => route('search')]]" />
        <div class="mt-4">
            <h1 class="text-3xl font-bold tracking-tight text-ink-900">
                @if($filters['q'] !== '')
                    Search results for “{{ $filters['q'] }}”
                @else
                    Search the catalogue
                @endif
            </h1>
            <p class="mt-2 text-sm text-ink-600">Exact SKU and model matches are prioritised. Search also checks brand names, aliases and product titles.</p>
        </div>

        <form method="get" action="{{ route('search') }}" class="mt-5 flex max-w-2xl gap-2" role="search">
            <label for="search-page-input" class="sr-only">Search products</label>
            <input id="search-page-input" type="search" name="q" value="{{ $filters['q'] }}" class="field-input" placeholder="Model, SKU, brand or product name">
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>

    <div class="container-page py-8">
        <div class="lg:grid lg:grid-cols-[260px_1fr] lg:gap-8">
            <aside class="hidden lg:block">
                <div class="card sticky top-4 p-4">
                    <h2 class="mb-4 text-sm font-bold text-ink-900">Filter results</h2>
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
                    <p class="text-sm text-ink-600">{{ number_format($products->total()) }} result{{ $products->total() === 1 ? '' : 's' }}</p>
                    @if($filters['q'] !== '')
                        <a href="{{ route('products.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Browse all products →</a>
                    @endif
                </div>

                @include('storefront.products.partials.grid', ['products' => $products, 'emptyMessage' => 'No products matched this search.'])
            </div>
        </div>
    </div>
@endsection
