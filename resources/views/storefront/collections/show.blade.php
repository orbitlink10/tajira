@extends('layouts.storefront')

@section('content')
    <div class="border-b border-ink-200 bg-white">
        <div class="container-page py-6">
            <x-breadcrumbs :crumbs="$crumbs" />
            <div class="mt-4 max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Curated collection</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">{{ $collection->title }}</h1>
                @if($collection->intro)
                    <div class="prose-content mt-3 text-sm">{!! $collection->intro !!}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="container-page py-8">
        <div class="lg:grid lg:grid-cols-[260px_1fr] lg:gap-8">
            <aside class="hidden lg:block">
                <div class="card sticky top-4 p-4">
                    <h2 class="mb-4 text-sm font-bold text-ink-900">Filter this collection</h2>
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

                <p class="mb-4 text-sm text-ink-600">{{ number_format($products->total()) }} selected product{{ $products->total() === 1 ? '' : 's' }}</p>
                @include('storefront.products.partials.grid', ['products' => $products])
            </div>
        </div>
    </div>
@endsection
