@extends('layouts.storefront')

@section('content')
    <div class="container-page py-16">
        <div class="mx-auto max-w-xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-700">404</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">We could not find that page.</h1>
            <p class="mt-3 text-sm leading-6 text-ink-600">The link may be outdated, or the product may have moved. Browse the catalogue, search by model, or ask us for help.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <a href="{{ route('home') }}" class="btn btn-primary">Go to homepage</a>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">Browse products</a>
                <a href="{{ route('contact') }}" class="btn btn-secondary">Contact us</a>
            </div>
            <form action="{{ route('search') }}" method="get" class="mt-8 flex gap-2" role="search">
                <label for="notfound-search" class="sr-only">Search products</label>
                <input id="notfound-search" name="q" type="search" class="field-input" placeholder="Search by model or brand">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>
@endsection
