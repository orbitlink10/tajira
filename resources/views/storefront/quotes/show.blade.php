@extends('layouts.storefront')

@section('content')
    <div class="container-page pt-6">
        <x-breadcrumbs :crumbs="[['name' => 'Home', 'url' => '/'], ['name' => 'Quotation', 'url' => route('quotes.show', $quotation->number)]]" />
    </div>

    <div class="container-page py-8">
        <div class="mx-auto max-w-3xl">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Quotation request</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-ink-900">{{ $quotation->number }}</h1>
                    <p class="mt-1 text-sm text-ink-500">Submitted {{ $quotation->created_at->format('j F Y, H:i') }}</p>
                </div>
                <span class="badge bg-ink-100 text-ink-800">{{ $quotation->statusLabel() }}</span>
            </div>

            <div class="card mt-6 p-6">
                <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                    Your request has been received. This page is a record of the request, not a final quotation. Our team will contact you with confirmed pricing, availability and terms.
                </div>

                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Contact</dt><dd class="mt-1 text-ink-900">{{ $quotation->name }}</dd><dd class="text-ink-600">{{ $quotation->email }}</dd><dd class="text-ink-600">{{ $quotation->phone }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Delivery context</dt><dd class="mt-1 text-ink-900">{{ $quotation->company ?: 'Individual' }}</dd><dd class="text-ink-600">{{ collect([$quotation->town, $quotation->county])->filter()->implode(', ') ?: 'Not specified' }}</dd></div>
                </dl>

                @if($quotation->items->isNotEmpty())
                    <div class="mt-6 overflow-hidden rounded-lg border border-ink-200">
                        <table class="w-full text-sm">
                            <thead class="bg-ink-50">
                                <tr><th class="px-3 py-2 text-left font-semibold text-ink-700">Item</th><th class="px-3 py-2 text-right font-semibold text-ink-700">Qty</th></tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                @foreach($quotation->items as $item)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <span class="font-medium text-ink-900">{{ $item->name }}</span>
                                            <span class="block text-xs text-ink-500">SKU {{ $item->sku ?: '—' }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-right text-ink-800">{{ $item->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($quotation->message)
                    <div class="mt-5">
                        <h2 class="text-sm font-bold text-ink-900">Your notes</h2>
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $quotation->message }}</p>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="{{ route('quotes.pdf', $quotation->number) }}" class="btn btn-secondary">Download request PDF</a>
                    <a href="{{ route('contact') }}" class="btn btn-primary">Contact Tajira</a>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Continue browsing</a>
                </div>
            </div>
        </div>
    </div>
@endsection
