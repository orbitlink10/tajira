@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Order '.$order->number, 'url' => route('orders.show', $order->number)], ['name' => 'Payment', 'url' => route('payments.show', $order->number)]]" />

        <div class="mt-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Payment</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-ink-900">{{ $order->formattedTotal() }}</h1>
                <p class="mt-1 text-sm text-ink-500">Order {{ $order->number }} · {{ $order->paymentStatusLabel() }}</p>
            </div>
            <a href="{{ route('orders.show', $order->number) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">View order →</a>
        </div>
    </div>

    <div class="container-page py-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-5">
                @php($latest = $order->payments->sortByDesc('id')->first())

                @if($latest && $latest->provider === 'mpesa' && in_array($latest->status, ['initiated', 'pending'], true))
                    <div class="card p-5" x-data="{ checked: false, init() { setInterval(() => this.check(), 5000); }, async check() { try { const r = await fetch('{{ route('payments.status', $order->number) }}', { headers: { Accept: 'application/json' } }); const d = await r.json(); if (d.paid) window.location.href = '{{ route('orders.show', $order->number) }}'; } catch (e) {} } }">
                        <h2 class="text-lg font-bold text-ink-900">Check your phone</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-600">We sent an M-Pesa request to {{ $latest->phone }}. Enter your M-Pesa PIN on your phone to approve {{ $order->formattedTotal() }}. This page updates automatically when the server confirms payment.</p>
                        <p class="mt-3 rounded-lg border border-ink-200 bg-ink-50 p-3 text-xs text-ink-600">Do not close this page until you see confirmation. If the prompt did not arrive, try again below or choose another method.</p>
                    </div>
                @endif

                <section class="card p-5">
                    <h2 class="text-lg font-bold text-ink-900">Choose a payment method</h2>
                    <div class="mt-4 space-y-4">
                        @forelse($methods as $value => $label)
                            <div class="rounded-xl border border-ink-200 p-4">
                                <h3 class="text-sm font-bold text-ink-900">{{ $label }}</h3>

                                @if($value === 'mpesa')
                                    <p class="mt-1 text-xs leading-5 text-ink-600">Enter the Safaricom number that should receive the payment prompt. The amount and currency are checked on our server.</p>
                                    <form method="post" action="{{ route('payments.pay', $order->number) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                                        @csrf
                                        <input type="hidden" name="payment_method" value="mpesa">
                                        <div class="flex-1">
                                            <label for="mpesa-phone" class="field-label">M-Pesa phone number</label>
                                            <input id="mpesa-phone" name="phone" value="{{ old('phone', $order->customer_phone) }}" inputmode="tel" class="field-input" placeholder="07xx xxx xxx" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Send STK request</button>
                                    </form>
                                @elseif($value === 'bank_transfer')
                                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                        @if($bank['name'])<div><dt class="text-xs text-ink-500">Bank</dt><dd class="font-semibold text-ink-900">{{ $bank['name'] }}</dd></div>@endif
                                        @if($bank['account_name'])<div><dt class="text-xs text-ink-500">Account name</dt><dd class="font-semibold text-ink-900">{{ $bank['account_name'] }}</dd></div>@endif
                                        @if($bank['account_number'])<div><dt class="text-xs text-ink-500">Account number</dt><dd class="font-semibold text-ink-900">{{ $bank['account_number'] }}</dd></div>@endif
                                        @if($bank['branch'])<div><dt class="text-xs text-ink-500">Branch</dt><dd class="font-semibold text-ink-900">{{ $bank['branch'] }}</dd></div>@endif
                                    </dl>
                                    <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Use <strong>{{ $order->number }}</strong> as the transfer reference. The order is marked paid only after our team verifies the bank credit. Do not send cash without confirmation.</p>
                                    <form method="post" action="{{ route('payments.pay', $order->number) }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="payment_method" value="bank_transfer">
                                        <button type="submit" class="btn btn-secondary">Show bank transfer instructions</button>
                                    </form>
                                @elseif($value === 'card')
                                    <p class="mt-1 text-xs text-ink-600">Card payments require an approved provider adapter. This option should not be enabled before integration and testing.</p>
                                @else
                                    <p class="mt-1 text-xs leading-5 text-ink-600">Submit the order and our team will contact you to arrange payment. The order remains unpaid until confirmed.</p>
                                    <form method="post" action="{{ route('payments.pay', $order->number) }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="payment_method" value="manual">
                                        <button type="submit" class="btn btn-secondary">Request payment instructions</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-600">No payment methods are enabled. Please contact Tajira with your order number.</p>
                        @endforelse
                    </div>
                </section>

                @if($latest)
                    <section class="card p-5">
                        <h2 class="text-sm font-bold text-ink-900">Payment attempts</h2>
                        <ul class="mt-3 divide-y divide-ink-100 text-sm">
                            @foreach($order->payments->sortByDesc('id') as $payment)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                    <span class="text-ink-700">{{ ucwords(str_replace('_', ' ', $payment->provider)) }} · {{ $payment->formattedAmount() }}</span>
                                    <span class="badge {{ $payment->status === 'succeeded' ? 'bg-emerald-50 text-emerald-800' : ($payment->status === 'failed' ? 'bg-red-50 text-red-800' : 'bg-ink-100 text-ink-700') }}">{{ ucfirst($payment->status) }}</span>
                                    @if($payment->failure_reason)<span class="w-full text-xs text-red-700">{{ $payment->failure_reason }}</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="space-y-4">
                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Order summary</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-600">Items</dt><dd>{{ $order->items->sum('quantity') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-600">Subtotal</dt><dd>{{ money($order->subtotal_minor) }}</dd></div>
                        @if($order->discount_minor > 0)<div class="flex justify-between"><dt class="text-ink-600">Discount</dt><dd>− {{ money($order->discount_minor) }}</dd></div>@endif
                        <div class="flex justify-between"><dt class="text-ink-600">Tax</dt><dd>{{ money($order->tax_minor) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-600">Delivery</dt><dd>{{ $order->shipping_minor === 0 ? 'Free' : money($order->shipping_minor) }}</dd></div>
                        <div class="flex justify-between border-t border-ink-200 pt-2 text-base font-bold"><dt>Total</dt><dd>{{ $order->formattedTotal() }}</dd></div>
                    </dl>
                </section>

                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Payment safety</h2>
                    <ul class="mt-2 space-y-2 text-xs leading-5 text-ink-600">
                        <li>We never ask for your M-Pesa PIN on this website.</li>
                        <li>A browser redirect alone never marks an order paid.</li>
                        <li>Callbacks are matched to the order, amount and currency before stock is committed.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
@endsection
