@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Account', 'url' => route('account.dashboard')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900">Your account</h1>
        <p class="mt-1 text-sm text-ink-600">Signed in as {{ $user->email }}</p>
    </div>

    <div class="container-page py-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('storefront.account.partials.nav')

            <div class="min-w-0 flex-1 space-y-5">
                @if($user->deletion_requested_at)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        A deletion request was recorded on {{ $user->deletion_requested_at->format('j F Y') }}. Our team will review it against accounting and legal retention obligations.
                    </div>
                @endif

                <div class="grid gap-3 sm:grid-cols-3">
                    <a href="{{ route('account.orders') }}" class="card p-5 hover:border-brand-300">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Orders</p>
                        <p class="mt-1 text-2xl font-bold text-ink-900">{{ $orderCount }}</p>
                    </a>
                    <a href="{{ route('account.wishlist') }}" class="card p-5 hover:border-brand-300">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Wishlist</p>
                        <p class="mt-1 text-2xl font-bold text-ink-900">{{ $wishlistCount }}</p>
                    </a>
                    <a href="{{ route('account.addresses') }}" class="card p-5 hover:border-brand-300">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Saved addresses</p>
                        <p class="mt-1 text-2xl font-bold text-ink-900">{{ $user->addresses()->count() }}</p>
                    </a>
                </div>

                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-ink-200 px-5 py-4">
                        <h2 class="text-base font-bold text-ink-900">Recent orders</h2>
                        <a href="{{ route('account.orders') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">View all</a>
                    </div>
                    @if($orders->isEmpty())
                        <div class="p-6 text-sm text-ink-600">You have not placed an order yet. <a class="font-semibold text-brand-700" href="{{ route('products.index') }}">Browse the catalogue</a>.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table-admin">
                                <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td class="font-semibold text-ink-900">{{ $order->number }}</td>
                                            <td>{{ $order->placed_at?->format('j M Y') }}</td>
                                            <td><span class="badge bg-ink-100 text-ink-700">{{ $order->statusLabel() }}</span></td>
                                            <td>{{ $order->formattedTotal() }}</td>
                                            <td><a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('orders.show', $order->number) }}">View</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <div class="rounded-xl border border-ink-200 bg-white p-5">
                    <h2 class="text-sm font-bold text-ink-900">Need a project quotation?</h2>
                    <p class="mt-1 text-sm text-ink-600">Send multiple models and quantities for a formal response from our team.</p>
                    <a href="{{ route('quotes.create') }}" class="btn btn-primary mt-3">Request a quotation</a>
                </div>
            </div>
        </div>
    </div>
@endsection
