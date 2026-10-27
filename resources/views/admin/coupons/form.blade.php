@extends('layouts.admin')

@section('title', $coupon->exists ? 'Edit coupon' : 'New coupon')
@section('heading', $coupon->exists ? 'Edit coupon' : 'New coupon')

@section('content')
    @php
        $displayValue = old('value', $coupon->exists
            ? ($coupon->type === 'percentage' ? $coupon->value / 100 : \App\Support\Money::minorToDecimal($coupon->value))
            : '');
    @endphp
    <form method="post" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="max-w-2xl space-y-4">
        @csrf
        @if($coupon->exists) @method('put') @endif
        <section class="card p-5 grid gap-4 sm:grid-cols-2">
            <div><label for="code" class="field-label">Code</label><input id="code" name="code" value="{{ old('code', $coupon->code) }}" required class="field-input uppercase"></div>
            <div><label for="type" class="field-label">Type</label>
                <select id="type" name="type" class="field-input">
                    <option value="percentage" @selected(old('type', $coupon->type) === 'percentage')>Percentage</option>
                    <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed amount (KES)</option>
                </select>
            </div>
            <div><label for="value" class="field-label">Value</label><input id="value" name="value" type="number" step="0.01" min="0" value="{{ $displayValue }}" required class="field-input"><p class="mt-1 text-xs text-ink-500">Percentage is entered as 10 for 10%. Fixed amount is in KES.</p></div>
            <div><label for="min_subtotal" class="field-label">Minimum subtotal (KES)</label><input id="min_subtotal" name="min_subtotal" type="number" step="0.01" min="0" value="{{ old('min_subtotal', $coupon->min_subtotal_minor !== null ? number_format($coupon->min_subtotal_minor / 100, 2, '.', '') : '') }}" class="field-input"></div>
            <div><label for="max_discount" class="field-label">Maximum discount (KES)</label><input id="max_discount" name="max_discount" type="number" step="0.01" min="0" value="{{ old('max_discount', $coupon->max_discount_minor !== null ? number_format($coupon->max_discount_minor / 100, 2, '.', '') : '') }}" class="field-input"></div>
            <div><label for="starts_at" class="field-label">Starts at</label><input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" class="field-input"></div>
            <div><label for="ends_at" class="field-label">Ends at</label><input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i')) }}" class="field-input"></div>
            <div><label for="usage_limit" class="field-label">Total usage limit</label><input id="usage_limit" name="usage_limit" type="number" min="1" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="field-input"></div>
            <div><label for="per_customer_limit" class="field-label">Per-customer limit</label><input id="per_customer_limit" name="per_customer_limit" type="number" min="1" value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}" class="field-input"></div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $coupon->is_active))> Active</label>
        </section>
        <div class="flex gap-2"><button class="btn btn-primary">Save coupon</button><a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary">Cancel</a></div>
    </form>
@endsection
