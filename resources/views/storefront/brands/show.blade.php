@extends('layouts.storefront')

@section('content')
    <div class="border-b border-ink-200 bg-white">
        <div class="container-page py-6">
            <x-breadcrumbs :crumbs="$crumbs" />
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-16 w-16 items-center justify-center rounded-xl bg-ink-900 text-xl font-bold text-white">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">{{ $brand->name }}</h1>
                        <p class="mt-1 text-sm text-ink-500">{{ $products->total() }} published product{{ $products->total() === 1 ? '' : 's' }}</p>
                    </div>
                </div>
                <a href="{{ route('quotes.create') }}" class="btn btn-primary w-fit">Request a quote</a>
            </div>
            @if($brand->description)
                <div class="mt-4 max-w-3xl text-sm leading-6 text-ink-600">{!! $brand->description !!}</div>
            @endif
        </div>
    </div>

    <div class="container-page py-8">
        @if($categories->isNotEmpty())
            <div class="mb-6 flex flex-wrap gap-2">
                @foreach($categories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}?brand[]={{ $brand->slug }}" class="rounded-full border border-ink-200 bg-white px-3 py-1.5 text-sm font-medium text-ink-700 hover:border-brand-300 hover:text-brand-800">
                        {{ $category->name }} <span class="text-xs text-ink-400">({{ $category->published_products_count }})</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="lg:grid lg:grid-cols-[260px_1fr] lg:gap-8">
            <aside class="hidden lg:block">
                <div class="card sticky top-4 p-4">
                    <h2 class="mb-4 text-sm font-bold text-ink-900">Filter {{ $brand->name }}</h2>
                    @include('storefront.products.partials.filters', ['filters' => $filters, 'filterGroups' => $filterGroups, 'filterBrands' => collect(), 'prefix' => 'desktop'])
                </div>
            </aside>

            <div>
                <details class="card mb-4 p-4 lg:hidden">
                    <summary class="cursor-pointer text-sm font-bold text-ink-900">Filters and sorting</summary>
                    <div class="mt-4">
                        @include('storefront.products.partials.filters', ['filters' => $filters, 'filterGroups' => $filterGroups, 'filterBrands' => collect(), 'prefix' => 'mobile'])
                    </div>
                </details>

                @include('storefront.products.partials.grid', ['products' => $products, 'emptyMessage' => 'No '.$brand->name.' products matched these filters.'])
            </div>
        </div>

        @if($brand->intro)
            <section class="mt-12 max-w-4xl">
                <h2 class="text-xl font-bold text-ink-900">About {{ $brand->name }}</h2>
                <div class="prose-content mt-3">{!! $brand->intro !!}</div>
            </section>
        @endif
    </div>
@endsection
