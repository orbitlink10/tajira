@extends('layouts.storefront')

@section('content')
    <div class="container-page py-16">
        <div class="mx-auto max-w-xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-700">419</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">Your session expired.</h1>
            <p class="mt-3 text-sm leading-6 text-ink-600">For your security, please go back, refresh the page and submit the form again.</p>
            <div class="mt-6"><a href="{{ url()->previous() }}" class="btn btn-primary">Go back</a></div>
        </div>
    </div>
@endsection
