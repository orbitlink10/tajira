@extends('layouts.storefront')

@section('content')
    <div class="container-page py-16">
        <div class="mx-auto max-w-xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-700">429</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">Too many requests.</h1>
            <p class="mt-3 text-sm leading-6 text-ink-600">Please wait a moment before trying again. This protects customer accounts and checkout.</p>
            <div class="mt-6"><a href="{{ route('home') }}" class="btn btn-primary">Go to homepage</a></div>
        </div>
    </div>
@endsection
