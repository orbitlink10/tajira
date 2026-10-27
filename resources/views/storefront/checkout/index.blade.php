@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Cart', 'url' => route('cart.index')], ['name' => 'Checkout', 'url' => route('checkout.index')]]" />
        <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-ink-900">Checkout</h1>
                <p class="mt-1 text-sm text-ink-600">No account required. We only collect what is needed to process and deliver your order.</p>
            </div>
            <a href="{{ route('cart.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">← Back to cart</a>
        </div>
    </div>

    <div class="container-page py-8">
        <form method="post" action="{{ route('checkout.store') }}" x-data="checkoutForm()" class="grid gap-6 lg:grid-cols-[1fr_360px]">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="space-y-5">
                <section class="card p-5">
                    <h2 class="text-lg font-bold text-ink-900">Contact details</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="field-label">Full name</label>
                            <input id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required autocomplete="name" class="field-input">
                            @error('customer_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="customer_phone" class="field-label">Phone number</label>
                            <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" required autocomplete="tel" inputmode="tel" class="field-input" placeholder="07xx xxx xxx">
                            @error('customer_phone')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="customer_email" class="field-label">Email address</label>
                            <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email', auth()->user()?->email) }}" required autocomplete="email" class="field-input">
                            <p class="mt-1 text-xs text-ink-500">Your order confirmation, payment updates and invoice are sent here.</p>
                            @error('customer_email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="company" class="field-label">Company or institution (optional)</label>
                            <input id="company" name="company" value="{{ old('company') }}" autocomplete="organization" class="field-input">
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-lg font-bold text-ink-900">Delivery method</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                            <input type="radio" name="delivery_type" value="delivery" class="mt-0.5 h-4 w-4 text-brand-700" @checked(old('delivery_type', $deliveryType) === 'delivery') @change="setDelivery('delivery')" required>
                            <span>
                                <span class="block text-sm font-semibold text-ink-900">Delivery</span>
                                <span class="mt-0.5 block text-xs text-ink-600">County-based delivery options and lead times.</span>
                            </span>
                        </label>
                        @if(setting('checkout.pickup_enabled'))
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="radio" name="delivery_type" value="pickup" class="mt-0.5 h-4 w-4 text-brand-700" @checked(old('delivery_type') === 'pickup') @change="setDelivery('pickup')">
                                <span>
                                    <span class="block text-sm font-semibold text-ink-900">Pickup</span>
                                    <span class="mt-0.5 block text-xs text-ink-600">{{ setting('checkout.pickup_instructions') ?: 'Collection details are confirmed after the order is accepted.' }}</span>
                                </span>
                            </label>
                        @endif
                    </div>
                    @error('delivery_type')<p class="field-error">{{ $message }}</p>@enderror

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="county" class="field-label">County</label>
                            <select id="county" name="county" x-ref="county" @change="loadMethods()" required class="field-input">
                                <option value="">Select a county</option>
                                @foreach(counties() as $countyName)
                                    <option value="{{ $countyName }}" @selected(old('county', $county) === $countyName)>{{ $countyName }}</option>
                                @endforeach
                            </select>
                            @error('county')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="town" class="field-label">Town or area</label>
                            <input id="town" name="town" value="{{ old('town') }}" required autocomplete="address-level2" class="field-input">
                            @error('town')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="address_line" class="field-label">Delivery address or landmark</label>
                            <input id="address_line" name="address_line" value="{{ old('address_line') }}" autocomplete="street-address" class="field-input" placeholder="Building, street, estate or pickup record">
                            @error('address_line')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="postal_code" class="field-label">Postal code (optional)</label>
                            <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}" autocomplete="postal-code" class="field-input">
                        </div>
                        <div>
                            <label for="delivery_notes" class="field-label">Delivery notes (optional)</label>
                            <input id="delivery_notes" name="delivery_notes" value="{{ old('delivery_notes') }}" class="field-input" placeholder="Access instructions">
                        </div>
                    </div>

                    <div class="mt-5">
                        <h3 class="field-label">Shipping option</h3>

                        <div x-show="!ready" class="space-y-2">
                            @forelse($methods as $method)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                    <input type="radio" name="shipping_method_id" value="{{ $method->id }}" class="mt-0.5 h-4 w-4 text-brand-700" @checked((string) old('shipping_method_id') === (string) $method->id)>
                                    <span class="flex-1">
                                        <span class="block text-sm font-semibold text-ink-900">{{ $method->name }}</span>
                                        <span class="text-xs text-ink-600">{{ $method->lead_time ?: 'Lead time confirmed with your order' }}</span>
                                    </span>
                                    <span class="text-sm font-semibold text-ink-900">{{ $method->formattedPrice() }}</span>
                                </label>
                            @empty
                                <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">No delivery method is currently configured for this county. You can still request a quotation.</p>
                            @endforelse
                        </div>

                        <div x-show="ready" x-cloak class="space-y-2">
                            <p x-show="loading" class="text-sm text-ink-500">Checking delivery options…</p>
                            <template x-for="method in methods" :key="method.id">
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                    <input type="radio" name="shipping_method_id" :value="String(method.id)" x-model="selected" class="mt-0.5 h-4 w-4 text-brand-700">
                                    <span class="flex-1">
                                        <span class="block text-sm font-semibold text-ink-900" x-text="method.name"></span>
                                        <span class="text-xs text-ink-600" x-text="method.lead_time || 'Lead time confirmed with your order'"></span>
                                    </span>
                                    <span class="text-sm font-semibold text-ink-900" x-text="method.price"></span>
                                </label>
                            </template>
                            <p x-show="!loading && methods.length === 0" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">No delivery method is currently configured for this county. You can still request a quotation.</p>
                        </div>
                        @error('shipping_method_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="card p-5">
                    <h2 class="text-lg font-bold text-ink-900">Payment</h2>
                    <p class="mt-1 text-xs text-ink-500">Only enabled and configured payment methods are shown. Payment is confirmed on the server, not from a browser redirect.</p>

                    <div class="mt-4 space-y-2">
                        @foreach($paymentMethods as $value => $label)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-ink-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="radio" name="payment_method" value="{{ $value }}" class="mt-0.5 h-4 w-4 text-brand-700" @checked(old('payment_method', array_key_first($paymentMethods)) === $value) required>
                                <span class="flex-1">
                                    <span class="block text-sm font-semibold text-ink-900">{{ $label }}</span>
                                    @if($value === 'mpesa')
                                        <span class="text-xs text-ink-600">An STK push is sent to your phone. Confirm the amount before approving.</span>
                                    @elseif($value === 'bank_transfer')
                                        <span class="text-xs text-ink-600">Bank details appear after you place the order. Payment is verified by our team.</span>
                                    @elseif($value === 'manual')
                                        <span class="text-xs text-ink-600">Submit the order and our team will contact you to arrange payment.</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method')<p class="field-error">{{ $message }}</p>@enderror

                    <div class="mt-4">
                        <label for="notes" class="field-label">Order notes (optional)</label>
                        <textarea id="notes" name="notes" rows="3" class="field-input" placeholder="Installation context, preferred delivery window or purchase order reference">{{ old('notes') }}</textarea>
                    </div>

                    @error('cart')<p class="field-error mt-3">{{ $message }}</p>@enderror
                    @error('checkout')<p class="field-error mt-3">{{ $message }}</p>@enderror
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-4 lg:self-start">
                <div class="card p-5">
                    <h2 class="text-base font-bold text-ink-900">Your order</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach($items as $item)
                            <li class="flex gap-3">
                                <img src="{{ $item['product']->imageUrl() }}" alt="" width="56" height="56" loading="lazy" class="h-14 w-14 rounded-md border border-ink-100 bg-white object-contain p-1">
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 text-xs font-medium text-ink-900">{{ $item['product']->name }}</p>
                                    <p class="text-xs text-ink-500">Qty {{ $item['quantity'] }}{{ $item['variant'] ? ' · '.$item['variant']->name : '' }}</p>
                                </div>
                                <p class="text-xs font-semibold text-ink-900">{{ money($item['line_total_minor']) }}</p>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="mt-4 space-y-2 border-t border-ink-200 pt-4 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-600">Subtotal</dt><dd class="font-medium">{{ money($totals['subtotal']) }}</dd></div>
                        @if($totals['discount'] > 0)
                            <div class="flex justify-between"><dt class="text-ink-600">Discount</dt><dd class="font-medium text-emerald-700">− {{ money($totals['discount']) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-ink-600">Tax</dt><dd class="font-medium">{{ money($totals['tax']) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-600">Delivery</dt><dd class="text-ink-600">According to method</dd></div>
                        <div class="flex justify-between border-t border-ink-200 pt-2 text-base font-bold"><dt>Estimated total</dt><dd>{{ money($totals['total']) }}</dd></div>
                    </dl>

                    <p class="mt-3 text-xs leading-5 text-ink-500">Stock is reserved for {{ setting('checkout.reservation_minutes', 30) }} minutes after the order is placed. The final amount is recalculated and verified on the server, including delivery and tax.</p>

                    <button type="submit" class="btn btn-primary mt-4 w-full py-3">Place order</button>
                    <p class="mt-2 text-center text-xs text-ink-500">By placing the order you agree to our <a class="underline hover:text-brand-700" href="{{ route('pages.show', 'terms-and-conditions') }}">terms</a> and <a class="underline hover:text-brand-700" href="{{ route('pages.show', 'privacy-policy') }}">privacy policy</a>.</p>
                </div>
            </aside>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'begin_checkout',
            ecommerce: {
                currency: '{{ config('tajira.currency', 'KES') }}',
                value: {{ $totals['total'] / 100 }},
                items: [
                    @foreach($items as $item)
                        {
                            item_id: @json($item['variant']?->sku ?? $item['product']->sku),
                            item_name: @json($item['product']->name),
                            item_brand: @json($item['product']->brand?->name),
                            price: {{ ($item['unit_price_minor'] ?? 0) / 100 }},
                            quantity: {{ $item['quantity'] }}
                        }{{ !$loop->last ? ',' : '' }}
                    @endforeach
                ]
            }
        });
    </script>
@endpush
