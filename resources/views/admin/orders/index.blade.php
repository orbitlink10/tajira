@extends('layouts.admin')

@section('title', 'Orders')
@section('heading', 'Orders')
@section('subheading', 'Payment-confirmed orders and pending checkouts.')

@section('content')
    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <div class="card p-4"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Confirmed revenue</p><p class="mt-1 text-2xl font-bold">{{ money((int) $summary['revenue']) }}</p></div>
        <div class="card p-4"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Unpaid / failed</p><p class="mt-1 text-2xl font-bold">{{ $summary['unpaid'] }}</p></div>
    </div>

    <form method="get" class="card mb-4 grid gap-3 p-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="q" class="field-label">Search</label>
            <input id="q" name="q" value="{{ request('q') }}" class="field-input" placeholder="Order number, name, email or phone">
        </div>
        <div>
            <label for="status" class="field-label">Order status</label>
            <select id="status" name="status" class="field-input">
                <option value="">All</option>
                @foreach(['pending_payment', 'paid', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="payment" class="field-label">Payment</label>
            <select id="payment" name="payment" class="field-input">
                <option value="">All</option>
                @foreach(['pending', 'paid', 'failed', 'cancelled', 'refunded'] as $status)
                    <option value="{{ $status }}" @selected(request('payment') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-4 flex gap-2"><button class="btn btn-secondary">Apply</button><a href="{{ route('admin.orders.index') }}" class="text-sm text-ink-600 underline self-center">Reset</a></div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $order->number }}</td>
                            <td>{{ $order->customer_name }}<span class="block text-xs text-ink-400">{{ $order->customer_email }} · {{ $order->county }}</span></td>
                            <td class="text-xs text-ink-500">{{ $order->placed_at?->format('j M Y, H:i') }}</td>
                            <td><span class="badge bg-ink-100 text-ink-700">{{ $order->statusLabel() }}</span></td>
                            <td><span class="badge {{ $order->isPaid() ? 'bg-emerald-50 text-emerald-800' : ($order->payment_status === 'failed' ? 'bg-red-50 text-red-800' : 'bg-amber-50 text-amber-900') }}">{{ $order->paymentStatusLabel() }}</span></td>
                            <td class="whitespace-nowrap">{{ $order->formattedTotal() }}</td>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="text-xs font-semibold text-brand-700">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No orders match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
