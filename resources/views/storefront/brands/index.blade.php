@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Brands', 'url' => route('brands.index')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Networking brands</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">Browse the brands represented in the Tajira catalogue. Brands are kept as separate entities, so you can also filter products by category and technical attributes.</p>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @forelse($brands as $brand)
                <a href="{{ route('brands.show', $brand->slug) }}" class="group card flex items-center gap-3 p-4 hover:border-brand-300">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-ink-50 text-sm font-bold text-ink-700">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                    <span>
                        <span class="block text-sm font-semibold text-ink-900 group-hover:text-brand-700">{{ $brand->name }}</span>
                        <span class="text-xs text-ink-500">{{ $brand->published_products_count }} products</span>
                    </span>
                </a>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-ink-300 bg-white p-8 text-sm text-ink-600">No brands are published yet.</p>
            @endforelse
        </div>
    </div>
@endsection
