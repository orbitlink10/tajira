@extends('layouts.admin')

@section('title', 'Shipping')
@section('heading', 'Shipping zones and methods')
@section('subheading', 'Counties, charges and lead times used at checkout.')

@section('content')
    <div class="grid gap-4 xl:grid-cols-[1fr_360px]">
        <div class="space-y-4">
            @forelse($zones as $zone)
                <section class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-ink-900">{{ $zone->name }}</h2>
                            <p class="mt-1 text-xs text-ink-500">{{ $zone->counties ? implode(', ', $zone->counties) : 'All counties' }}</p>
                            <p class="text-xs text-ink-400">{{ $zone->is_active ? 'Active' : 'Inactive' }}</p>
                        </div>
                        <form method="post" action="{{ route('admin.shipping.zones.destroy', $zone) }}" onsubmit="return confirm('Delete this zone?');">
                            @csrf @method('delete')
                            <button class="text-xs font-semibold text-red-700">Delete zone</button>
                        </form>
                    </div>

                    <details class="mt-3 rounded-lg border border-ink-200 p-3">
                        <summary class="cursor-pointer text-xs font-semibold text-ink-700">Edit zone</summary>
                        <form method="post" action="{{ route('admin.shipping.zones.update', $zone) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                            @csrf @method('put')
                            <div><label class="field-label" for="zone-name-{{ $zone->id }}">Name</label><input id="zone-name-{{ $zone->id }}" name="name" value="{{ $zone->name }}" required class="field-input"></div>
                            <div><label class="field-label" for="zone-pos-{{ $zone->id }}">Position</label><input id="zone-pos-{{ $zone->id }}" name="position" type="number" min="0" value="{{ $zone->position }}" class="field-input"></div>
                            <div class="sm:col-span-2"><label class="field-label" for="zone-counties-{{ $zone->id }}">Counties (comma or line separated; blank = all)</label><textarea id="zone-counties-{{ $zone->id }}" name="counties_text" rows="2" class="field-input">{{ implode(', ', $zone->counties ?? []) }}</textarea></div>
                            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked($zone->is_active)> Active</label>
                            <button class="btn btn-secondary w-fit sm:col-span-2">Save zone</button>
                        </form>
                    </details>

                    <div class="mt-4 space-y-2">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-ink-500">Methods</h3>
                        @forelse($zone->methods as $method)
                            <details class="rounded-lg border border-ink-200 p-3">
                                <summary class="flex cursor-pointer items-center justify-between gap-2 text-sm">
                                    <span class="font-semibold text-ink-900">{{ $method->name }} <span class="badge ml-1 bg-ink-100 text-ink-700">{{ ucfirst($method->type) }}</span></span>
                                    <span class="font-medium">{{ $method->formattedPrice() }}</span>
                                </summary>
                                <form method="post" action="{{ route('admin.shipping.methods.update', $method) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                                    @csrf @method('put')
                                    <input type="hidden" name="shipping_zone_id" value="{{ $zone->id }}">
                                    <div><label class="field-label" for="m-name-{{ $method->id }}">Name</label><input id="m-name-{{ $method->id }}" name="name" value="{{ $method->name }}" required class="field-input"></div>
                                    <div><label class="field-label" for="m-code-{{ $method->id }}">Code</label><input id="m-code-{{ $method->id }}" name="code" value="{{ $method->code }}" class="field-input"></div>
                                    <div><label class="field-label" for="m-type-{{ $method->id }}">Type</label><select id="m-type-{{ $method->id }}" name="type" class="field-input">@foreach(['flat' => 'Flat rate', 'free' => 'Free', 'pickup' => 'Pickup'] as $key => $label)<option value="{{ $key }}" @selected($method->type === $key)>{{ $label }}</option>@endforeach</select></div>
                                    <div><label class="field-label" for="m-price-{{ $method->id }}">Price (KES)</label><input id="m-price-{{ $method->id }}" name="price" type="number" step="0.01" min="0" value="{{ number_format($method->price_minor / 100, 2, '.', '') }}" class="field-input"></div>
                                    <div><label class="field-label" for="m-lead-{{ $method->id }}">Lead time</label><input id="m-lead-{{ $method->id }}" name="lead_time" value="{{ $method->lead_time }}" class="field-input"></div>
                                    <div><label class="field-label" for="m-pos-{{ $method->id }}">Position</label><input id="m-pos-{{ $method->id }}" name="position" type="number" min="0" value="{{ $method->position }}" class="field-input"></div>
                                    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked($method->is_active)> Active</label>
                                    <div class="flex items-center gap-3 sm:col-span-2"><button class="btn btn-secondary" type="submit">Save method</button></div>
                                </form>
                                <form method="post" action="{{ route('admin.shipping.methods.destroy', $method) }}" class="mt-2" onsubmit="return confirm('Delete this method?');">
                                    @csrf @method('delete')
                                    <button class="text-xs font-semibold text-red-700">Delete method</button>
                                </form>
                            </details>
                        @empty
                            <p class="text-sm text-ink-500">No methods in this zone.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <p class="rounded-lg border border-dashed border-ink-300 bg-white p-8 text-sm text-ink-500">No shipping zones yet. Create one to control county delivery.</p>
            @endforelse
        </div>

        <aside class="space-y-4">
            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">New zone</h2>
                <form method="post" action="{{ route('admin.shipping.zones.store') }}" class="mt-3 space-y-3">
                    @csrf
                    <div><label for="zone_name" class="field-label">Name</label><input id="zone_name" name="name" required class="field-input" placeholder="Nairobi metro"></div>
                    <div><label for="zone_counties" class="field-label">Counties (blank = all)</label><textarea id="zone_counties" name="counties_text" rows="3" class="field-input" placeholder="Nairobi City, Kiambu, Machakos"></textarea></div>
                    <div><label for="zone_position" class="field-label">Position</label><input id="zone_position" name="position" type="number" min="0" value="0" class="field-input"></div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" checked> Active</label>
                    <button class="btn btn-primary w-full">Create zone</button>
                </form>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">New method</h2>
                <form method="post" action="{{ route('admin.shipping.methods.store') }}" class="mt-3 space-y-3">
                    @csrf
                    <div><label for="method_name" class="field-label">Name</label><input id="method_name" name="name" required class="field-input" placeholder="Nairobi delivery"></div>
                    <div><label for="method_zone" class="field-label">Zone</label><select id="method_zone" name="shipping_zone_id" class="field-input"><option value="">All zones</option>@foreach($zones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }}</option>@endforeach</select></div>
                    <div><label for="method_type" class="field-label">Type</label><select id="method_type" name="type" class="field-input"><option value="flat">Flat rate</option><option value="free">Free</option><option value="pickup">Pickup</option></select></div>
                    <div><label for="method_price" class="field-label">Price (KES)</label><input id="method_price" name="price" type="number" step="0.01" min="0" class="field-input"></div>
                    <div><label for="method_lead" class="field-label">Lead time</label><input id="method_lead" name="lead_time" class="field-input" placeholder="1–2 working days"></div>
                    <div><label for="method_position" class="field-label">Position</label><input id="method_position" name="position" type="number" min="0" value="0" class="field-input"></div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" checked> Active</label>
                    <button class="btn btn-primary w-full">Create method</button>
                </form>
            </section>

            @if($unassigned->isNotEmpty())
                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Methods without a zone</h2>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach($unassigned as $method)
                            <li>{{ $method->name }} · {{ $method->formattedPrice() }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
@endsection
