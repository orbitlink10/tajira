@extends('layouts.storefront')

@section('content')
    <div class="border-b border-ink-200 bg-white">
        <div class="container-page py-6">
            <x-breadcrumbs :crumbs="$crumbs" />
            <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <h1 class="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">{{ $category->name }}</h1>
                    @if($category->description)
                        <p class="mt-3 text-sm leading-6 text-ink-600">{{ $category->description }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('quotes.create') }}" class="btn btn-secondary">Request a quote</a>
                    <a href="{{ route('products.index') }}" class="btn btn-primary">All products</a>
                </div>
            </div>

            @if($category->activeChildren->isNotEmpty())
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach($category->activeChildren as $child)
                        <a href="{{ route('categories.show', $child->slug) }}" class="rounded-full border border-ink-200 bg-ink-50 px-3 py-1.5 text-sm font-medium text-ink-700 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-800">
                            {{ $child->name }} <span class="text-xs text-ink-400">({{ $child->published_products_count }})</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="container-page py-8">
        <div class="lg:grid lg:grid-cols-[260px_1fr] lg:gap-8">
            <aside class="hidden lg:block">
                <div class="card sticky top-4 p-4">
                    <h2 class="mb-4 text-sm font-bold text-ink-900">Filter {{ $category->name }}</h2>
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
                    <a href="{{ route('quotes.create') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Need a project quote? →</a>
                </div>

                @include('storefront.products.partials.grid', ['products' => $products])
            </div>
        </div>

        @if($category->buying_advice)
            <section class="mt-12 max-w-4xl">
                <h2 class="text-xl font-bold text-ink-900">Buying advice</h2>
                <div class="prose-content mt-3">{!! $category->buying_advice !!}</div>
            </section>
        @endif
    </div>
@endsection
