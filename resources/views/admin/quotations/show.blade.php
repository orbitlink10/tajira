@extends('layouts.admin')

@section('title', 'Quotation '.$quotation->number)
@section('heading', 'Quotation '.$quotation->number)
@section('subheading', $quotation->created_at->format('j F Y, H:i').' · '.$quotation->statusLabel())

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.quotations.index') }}" class="text-sm font-semibold text-brand-700">← All quotations</a>
        <a href="{{ route('admin.quotations.pdf', $quotation) }}" class="btn btn-secondary ml-auto">Download PDF</a>
        <a href="mailto:{{ $quotation->email }}?subject={{ rawurlencode('Quotation '.$quotation->number) }}" class="btn btn-secondary">Email customer</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr_380px]">
        <div class="space-y-4">
            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Request details</h2>
                <div class="mt-3 grid gap-4 text-sm sm:grid-cols-2">
                    <div class="leading-6 text-ink-700">
                        <p class="font-semibold text-ink-900">{{ $quotation->name }}</p>
                        @if($quotation->company)<p>{{ $quotation->company }}</p>@endif
                        <p><a class="text-brand-700" href="mailto:{{ $quotation->email }}">{{ $quotation->email }}</a></p>
                        <p>{{ $quotation->phone }}</p>
                        <p>{{ collect([$quotation->town, $quotation->county])->filter()->implode(', ') ?: 'Location not provided' }}</p>
                    </div>
                    <div class="text-ink-700">
                        <p>Status: <strong>{{ $quotation->statusLabel() }}</strong></p>
                        @if($quotation->valid_until)<p>Valid until: {{ $quotation->valid_until->format('j F Y') }}</p>@endif
                        @if($quotation->total_minor !== null)<p>Indicative total: <strong>{{ $quotation->formattedTotal() }}</strong></p>@endif
                    </div>
                </div>
                @if($quotation->message)
                    <div class="mt-4 rounded-lg bg-ink-50 p-3 text-sm leading-6 text-ink-700 whitespace-pre-line">{{ $quotation->message }}</div>
                @endif
            </section>

            <section class="card overflow-hidden">
                <h2 class="border-b border-ink-200 px-5 py-4 text-sm font-bold text-ink-900">Requested items</h2>
                @if($quotation->items->isEmpty())
                    <p class="p-5 text-sm text-ink-500">No specific items were selected.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table-admin">
                            <thead><tr><th>Item</th><th>SKU</th><th>Qty</th><th>Indicative unit</th><th>Product</th></tr></thead>
                            <tbody>
                                @foreach($quotation->items as $item)
                                    <tr>
                                        <td class="font-medium text-ink-900">{{ $item->name }}</td>
                                        <td class="text-xs">{{ $item->sku ?: '—' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->unit_price_minor !== null ? money($item->unit_price_minor) : 'TBC' }}</td>
                                        <td>@if($item->product)<a href="{{ route('admin.products.edit', $item->product) }}" class="text-xs font-semibold text-brand-700">Edit product</a>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            @can('quotations.manage')
                <section class="card p-5">
                    <h2 class="text-sm font-bold text-ink-900">Respond and track</h2>
                    <form method="post" action="{{ route('admin.quotations.update', $quotation) }}" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label for="status" class="field-label">Status</label>
                            <select id="status" name="status" class="field-input">
                                @foreach(['new', 'reviewed', 'quoted', 'accepted', 'declined', 'expired'] as $status)
                                    <option value="{{ $status }}" @selected($quotation->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label for="total" class="field-label">Quoted total (KES)</label><input id="total" name="total" type="number" step="0.01" min="0" value="{{ $quotation->total_minor !== null ? number_format($quotation->total_minor / 100, 2, '.', '') : '' }}" class="field-input"></div>
                        <div><label for="valid_until" class="field-label">Valid until</label><input id="valid_until" name="valid_until" type="date" value="{{ $quotation->valid_until?->format('Y-m-d') }}" class="field-input"></div>
                        <div><label for="admin_notes" class="field-label">Notes to include in email/PDF</label><textarea id="admin_notes" name="admin_notes" rows="5" class="field-input">{{ $quotation->admin_notes }}</textarea></div>
                        <label class="flex items-center gap-2 text-xs text-ink-600"><input type="checkbox" name="send_email" value="1" class="h-4 w-4 rounded"> Email this update to the customer</label>
                        <button class="btn btn-primary w-full">Save quotation</button>
                    </form>
                </section>
            @endcan

            <section class="card p-5 text-xs text-ink-500">
                <p>Quotation totals are commercial data controlled by Tajira. The customer-facing page describes an accepted request as indicative until Tajira confirms it.</p>
            </section>
        </aside>
    </div>
@endsection
