@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Track an order', 'url' => route('orders.track')]]" />
    </div>

    <div class="container-page py-10">
        <div class="mx-auto max-w-md">
            <h1 class="text-3xl font-bold tracking-tight text-ink-900">Track your order</h1>
            <p class="mt-2 text-sm leading-6 text-ink-600">Enter the order number and the email address used at checkout. This protects your order details.</p>

            <form method="post" action="{{ route('orders.find') }}" class="card mt-6 space-y-4 p-6">
                @csrf
                <div>
                    <label for="number" class="field-label">Order number</label>
                    <input id="number" name="number" value="{{ old('number') }}" required class="field-input" placeholder="TJ-20261020-ABC123" autocomplete="off">
                    @error('number')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="field-label">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required class="field-input" autocomplete="email">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary w-full py-3">Find my order</button>
            </form>

            <p class="mt-4 text-center text-sm text-ink-600">Already signed in? <a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('account.orders') }}">View your orders</a>.</p>
        </div>
    </div>
@endsection
