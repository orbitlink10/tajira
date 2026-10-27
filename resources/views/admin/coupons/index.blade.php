@extends('layouts.admin')

@section('title', 'Coupons')
@section('heading', 'Coupons')
@section('subheading', 'Configured discounts; values are stored in minor units or basis points.')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-ink-600">{{ $coupons->total() }} coupons</p>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">New coupon</a>
    </div>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Code</th><th>Value</th><th>Minimum</th><th>Usage</th><th>Window</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $coupon->code }}</td>
                            <td>{{ $coupon->displayValue() }}</td>
                            <td>{{ $coupon->min_subtotal_minor !== null ? money($coupon->min_subtotal_minor) : '—' }}</td>
                            <td>{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
                            <td class="text-xs text-ink-500">{{ $coupon->starts_at?->format('j M Y') ?: 'Now' }} – {{ $coupon->ends_at?->format('j M Y') ?: 'No end' }}</td>
                            <td><span class="badge {{ $coupon->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-ink-100 text-ink-700' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-xs font-semibold text-brand-700">Edit</a>
                                <form method="post" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline" onsubmit="return confirm('Delete this coupon?');">
                                    @csrf @method('delete')
                                    <button class="ml-2 text-xs font-semibold text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No coupons yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $coupons->links() }}</div>
@endsection
