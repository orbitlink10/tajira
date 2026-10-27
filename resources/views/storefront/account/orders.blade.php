@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Account', 'url' => route('account.dashboard')], ['name' => 'Orders', 'url' => route('account.orders')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900">Your orders</h1>
    </div>

    <div class="container-page py-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('storefront.account.partials.nav')

            <div class="min-w-0 flex-1">
                @if($orders->isEmpty())
                    <div class="rounded-xl border border-dashed border-ink-300 bg-white p-10 text-center">
                        <p class="font-semibold text-ink-900">No orders yet.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary mt-4">Browse products</a>
                    </div>
                @else
                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="table-admin">
                                <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Status</th><th>Total</th><th></th></tr></thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td class="font-semibold text-ink-900">{{ $order->number }}</td>
                                            <td>{{ $order->placed_at?->format('j M Y') }}</td>
                                            <td>{{ $order->items_count }}</td>
                                            <td>
                                                <span class="badge bg-ink-100 text-ink-700">{{ $order->statusLabel() }}</span>
                                                <span class="badge {{ $order->isPaid() ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">{{ $order->paymentStatusLabel() }}</span>
                                            </td>
                                            <td>{{ $order->formattedTotal() }}</td>
                                            <td class="whitespace-nowrap"><a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('orders.show', $order->number) }}">View</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-6">{{ $orders->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
