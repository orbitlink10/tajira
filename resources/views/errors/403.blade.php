@extends('layouts.storefront')

@section('content')
    <div class="container-page py-16">
        <div class="mx-auto max-w-xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-700">403</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">You do not have access to this page.</h1>
            <p class="mt-3 text-sm leading-6 text-ink-600">If you followed an order or quotation link, make sure you are signed in with the right account.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
                <a href="{{ route('home') }}" class="btn btn-secondary">Go to homepage</a>
                <a href="{{ route('contact') }}" class="btn btn-secondary">Contact us</a>
            </div>
        </div>
    </div>
@endsection
