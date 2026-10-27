@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Request a quotation', 'url' => route('quotes.create')]]" />
    </div>

    <div class="container-page py-8">
        <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Request a quotation</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600">Send the products, quantities and project context. We will respond with pricing, availability, warranty and delivery terms. A quotation is not an order and does not reserve stock.</p>

                <form method="post" action="{{ route('quotes.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <section class="card p-5">
                        <h2 class="text-lg font-bold text-ink-900">Your details</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="name" class="field-label">Full name</label>
                                <input id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required autocomplete="name" class="field-input">
                                @error('name')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="field-label">Phone</label>
                                <input id="phone" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required autocomplete="tel" class="field-input">
                                @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="email" class="field-label">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required autocomplete="email" class="field-input">
                                @error('email')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="company" class="field-label">Company or institution (optional)</label>
                                <input id="company" name="company" value="{{ old('company') }}" autocomplete="organization" class="field-input">
                            </div>
                            <div>
                                <label for="county" class="field-label">County (optional)</label>
                                <select id="county" name="county" class="field-input">
                                    <option value="">Select county</option>
                                    @foreach(counties() as $countyName)
                                        <option value="{{ $countyName }}" @selected(old('county') === $countyName)>{{ $countyName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="town" class="field-label">Town or area (optional)</label>
                                <input id="town" name="town" value="{{ old('town') }}" class="field-input">
                            </div>
                        </div>
                    </section>

                    <section class="card p-5">
                        <h2 class="text-lg font-bold text-ink-900">Products</h2>
                        @if(count($items))
                            <div class="mt-4 space-y-3">
                                @foreach($items as $index => $item)
                                    <div class="flex flex-col gap-3 rounded-lg border border-ink-200 p-3 sm:flex-row sm:items-center">
                                        <img src="{{ $item['product']->imageUrl() }}" alt="" width="56" height="56" loading="lazy" class="h-14 w-14 rounded border border-ink-100 object-contain">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-ink-900">{{ $item['product']->name }}</p>
                                            <p class="text-xs text-ink-500">SKU {{ $item['variant']?->sku ?? $item['product']->sku }}{{ $item['variant'] ? ' · '.$item['variant']->name : '' }}</p>
                                        </div>
                                        <div class="w-28">
                                            <label for="qty-{{ $index }}" class="sr-only">Quantity for {{ $item['product']->name }}</label>
                                            <input id="qty-{{ $index }}" type="number" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity', $item['quantity']) }}" min="1" max="10000" class="field-input">
                                        </div>
                                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product']->id }}">
                                    </div>
                                @endforeach
                                <p class="text-xs text-ink-500">Prices shown in the catalogue are indicative. The quotation confirms the final price and availability.</p>
                            </div>
                        @else
                            <p class="mt-2 text-sm text-ink-600">No products selected yet. Describe what you need below, or browse the catalogue and use “Request quote” on a product.</p>
                        @endif

                        <div class="mt-4">
                            <label for="message" class="field-label">What do you need? (optional if products are listed)</label>
                            <textarea id="message" name="message" rows="5" class="field-input" placeholder="Project scope, installation location, quantities, required specifications or purchase order details">{{ old('message') }}</textarea>
                            @error('message')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    @error('items')<p class="field-error">{{ $message }}</p>@enderror

                    <button type="submit" class="btn btn-primary w-full py-3 sm:w-auto">Send quotation request</button>
                    <p class="text-xs text-ink-500">We use these details only to respond to your request and manage the business relationship.</p>
                </form>
            </div>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Useful for</h2>
                    <ul class="mt-3 space-y-2 text-sm text-ink-700">
                        <li>Bulk equipment purchases</li>
                        <li>Installer and ISP projects</li>
                        <li>Institutional procurement</li>
                        <li>Mixed bills of materials</li>
                        <li>Products without an online price</li>
                    </ul>
                </div>
                <div class="rounded-xl border border-ink-200 bg-ink-50 p-5">
                    <h2 class="text-sm font-bold text-ink-900">What happens next</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-ink-600">
                        <li>Our team reviews the requested items and specifications.</li>
                        <li>We confirm price, availability, lead time, warranty and delivery.</li>
                        <li>You receive a quotation document that you can approve.</li>
                    </ol>
                </div>
                @if(whatsapp_url())
                    <a href="{{ whatsapp_url('Hello Tajira, I would like a quotation.') }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary w-full">Prefer WhatsApp?</a>
                @endif
            </aside>
        </div>
    </div>
@endsection
