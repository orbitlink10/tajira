@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Account', 'url' => route('account.dashboard')], ['name' => 'Addresses', 'url' => route('account.addresses')]]" />
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-ink-900">Saved addresses</h1>
    </div>

    <div class="container-page py-8">
        <div class="flex flex-col gap-6 lg:flex-row">
            @include('storefront.account.partials.nav')

            <div class="min-w-0 flex-1 space-y-5">
                @forelse($addresses as $address)
                    <div class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="text-sm leading-6 text-ink-700">
                            <p class="font-semibold text-ink-900">{{ $address->label ?: 'Address' }} @if($address->is_default)<span class="badge bg-brand-50 text-brand-800">Default</span>@endif</p>
                            <p>{{ $address->recipient }} · {{ $address->phone }}</p>
                            <p>{{ $address->address_line }}</p>
                            <p>{{ $address->town }}, {{ $address->county }} @if($address->postal_code) · {{ $address->postal_code }}@endif</p>
                            @if($address->delivery_notes)<p class="text-xs text-ink-500">{{ $address->delivery_notes }}</p>@endif
                        </div>
                        <form method="post" action="{{ route('account.addresses.delete', $address) }}">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900">Delete</button>
                        </form>
                    </div>
                @empty
                    <p class="rounded-lg border border-dashed border-ink-300 bg-white p-6 text-sm text-ink-600">No saved addresses yet.</p>
                @endforelse

                <section class="card p-5">
                    <h2 class="text-base font-bold text-ink-900">Add an address</h2>
                    <form method="post" action="{{ route('account.addresses.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label for="label" class="field-label">Label (optional)</label>
                            <input id="label" name="label" value="{{ old('label') }}" class="field-input" placeholder="Office, site, home">
                        </div>
                        <div>
                            <label for="recipient" class="field-label">Recipient</label>
                            <input id="recipient" name="recipient" value="{{ old('recipient', auth()->user()->name) }}" required class="field-input">
                        </div>
                        <div>
                            <label for="phone" class="field-label">Phone</label>
                            <input id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" required class="field-input">
                        </div>
                        <div>
                            <label for="county" class="field-label">County</label>
                            <select id="county" name="county" required class="field-input">
                                <option value="">Select county</option>
                                @foreach(counties() as $countyName)
                                    <option value="{{ $countyName }}" @selected(old('county') === $countyName)>{{ $countyName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="town" class="field-label">Town or area</label>
                            <input id="town" name="town" value="{{ old('town') }}" required class="field-input">
                        </div>
                        <div>
                            <label for="postal_code" class="field-label">Postal code (optional)</label>
                            <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}" class="field-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="address_line" class="field-label">Address or landmark</label>
                            <input id="address_line" name="address_line" value="{{ old('address_line') }}" required class="field-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="delivery_notes" class="field-label">Delivery notes (optional)</label>
                            <input id="delivery_notes" name="delivery_notes" value="{{ old('delivery_notes') }}" class="field-input">
                        </div>
                        <label class="flex items-center gap-2 text-sm text-ink-700 sm:col-span-2">
                            <input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-ink-300 text-brand-700">
                            Use as my default address
                        </label>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn btn-primary">Save address</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
