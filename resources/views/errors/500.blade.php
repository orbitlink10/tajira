@extends('layouts.storefront')

@section('content')
    <div class="container-page py-16">
        <div class="mx-auto max-w-xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-700">500</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">Something went wrong.</h1>
            <p class="mt-3 text-sm leading-6 text-ink-600">We have recorded the error and will investigate. Please try again shortly. If an order payment was involved, do not pay twice; contact us with your order number.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <a href="{{ route('home') }}" class="btn btn-primary">Go to homepage</a>
                <a href="{{ route('contact') }}" class="btn btn-secondary">Contact us</a>
            </div>
        </div>
    </div>
@endsection
