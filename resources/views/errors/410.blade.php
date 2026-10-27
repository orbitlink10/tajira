@extends('layouts.storefront')

@section('content')
    <div class="container-page py-16">
        <div class="mx-auto max-w-xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-700">410</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">This page has been permanently removed.</h1>
            <p class="mt-3 text-sm leading-6 text-ink-600">The product or page is no longer available. Browse current products or ask us for an equivalent recommendation.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <a href="{{ route('products.index') }}" class="btn btn-primary">Browse products</a>
                <a href="{{ route('contact') }}" class="btn btn-secondary">Ask for an alternative</a>
            </div>
        </div>
    </div>
@endsection
