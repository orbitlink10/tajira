@extends('layouts.admin')

@section('title', 'Import '.$batch->reference)
@section('heading', 'Import '.$batch->reference)
@section('subheading', $batch->type === 'csv' ? 'Owner-supplied CSV' : 'Catalogue discovery batch')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.imports.index') }}" class="text-sm font-semibold text-brand-700">← Reconciliation dashboard</a>
        @if($batch->type === 'discovery')
            <form method="post" action="{{ route('admin.imports.fetch', $batch) }}" class="ml-auto">
                @csrf
                <input type="hidden" name="limit" value="25">
                <button class="btn btn-primary" type="submit">Fetch next 25 pages</button>
            </form>
            <form method="post" action="{{ route('admin.imports.fetch', $batch) }}">
                @csrf
                <input type="hidden" name="limit" value="100">
                <button class="btn btn-secondary" type="submit">Fetch next 100</button>
            </form>
        @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4"><p class="text-xs text-ink-500">Status</p><p class="mt-1 text-xl font-bold">{{ $batch->statusLabel() }}</p><p class="text-xs text-ink-500">{{ $batch->processed_rows }}/{{ $batch->total_rows }} processed</p></div>
        <div class="card p-4"><p class="text-xs text-ink-500">Drafts created</p><p class="mt-1 text-xl font-bold">{{ $batch->imported_rows }}</p></div>
        <div class="card p-4"><p class="text-xs text-ink-500">Duplicates flagged</p><p class="mt-1 text-xl font-bold">{{ $batch->duplicate_rows }}</p></div>
        <div class="card p-4"><p class="text-xs text-ink-500">Failed rows</p><p class="mt-1 text-xl font-bold">{{ $batch->failed_rows }}</p></div>
    </div>

    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        {{ $batch->notes ?: 'Staged observations require review. Imported products are drafts; specifications, images, prices and warranty must be approved separately.' }}
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.imports.show', $batch) }}" class="badge {{ !request('status') ? 'bg-ink-900 text-white' : 'bg-white border border-ink-200 text-ink-700' }}">All ({{ $batch->total_rows }})</a>
        @foreach(['discovered', 'needs_review', 'imported', 'duplicate', 'failed', 'rejected'] as $status)
            <a href="{{ route('admin.imports.show', ['batch' => $batch, 'status' => $status]) }}" class="badge {{ request('status') === $status ? 'bg-ink-900 text-white' : 'bg-white border border-ink-200 text-ink-700' }}">{{ ucwords(str_replace('_', ' ', $status)) }} ({{ $statusCounts[$status] ?? 0 }})</a>
        @endforeach
    </div>

    <div class="mt-4 card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Source record</th><th>Observed</th><th>Status</th><th>Errors / notes</th><th>Decision</th></tr></thead>
                <tbody>
                    @forelse($sources as $source)
                        <tr>
                            <td class="max-w-sm">
                                <span class="font-semibold text-ink-900">{{ $source->raw_name ?: 'Unnamed source record' }}</span>
                                <span class="block text-xs text-ink-500">{{ $source->raw_brand ?: 'Brand unknown' }} · {{ $source->raw_model ?: 'Model unknown' }} · {{ $source->raw_mpn ? 'MPN '.$source->raw_mpn : 'No MPN' }}</span>
                                <a class="text-xs font-semibold text-brand-700" href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer">Source URL ↗</a>
                                <span class="block text-xs text-ink-400">Proposed SKU: {{ $source->proposed_sku ?: '—' }}</span>
                            </td>
                            <td class="text-xs">
                                @if($source->raw_price_minor !== null)<span class="block">{{ money($source->raw_price_minor) }} {{ $source->raw_currency }}</span><span class="text-ink-400">Competitor reference only</span>@endif
                                @if(!empty($source->raw_categories))<span class="mt-1 block text-ink-500">{{ implode(' · ', array_slice($source->raw_categories, 0, 3)) }}</span>@endif
                            </td>
                            <td><span class="badge bg-ink-100 text-ink-700">{{ $source->statusLabel() }}</span>@if($source->product_id)<a class="mt-1 block text-xs font-semibold text-brand-700" href="{{ route('admin.products.edit', $source->product_id) }}">Open product #{{ $source->product_id }}</a>@endif</td>
                            <td class="max-w-xs text-xs text-ink-600">
                                @if($source->errors)
                                    <ul class="list-disc pl-4">@foreach($source->errors as $error)<li>{{ is_array($error) ? ($error['message'] ?? json_encode($error)) : $error }}</li>@endforeach</ul>
                                @endif
                                @if($source->verification_notes)<p class="mt-1">{{ $source->verification_notes }}</p>@endif
                            </td>
                            <td class="min-w-[220px]">
                                @if(in_array($source->status, ['needs_review'], true))
                                    <form method="post" action="{{ route('admin.imports.approve', $source) }}" class="space-y-2">
                                        @csrf
                                        <input name="sku" value="{{ $source->proposed_sku }}" class="field-input text-xs" placeholder="SKU">
                                        <select name="brand_id" class="field-input text-xs">
                                            <option value="">Match brand by name</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="category_id" class="field-input text-xs">
                                            <option value="">Match category by source name</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-primary w-full px-2 py-1.5 text-xs" type="submit">Approve as draft</button>
                                    </form>
                                @endif
                                @if(in_array($source->status, ['failed', 'discovered'], true))
                                    <form method="post" action="{{ route('admin.imports.retry', $source) }}">@csrf<button class="btn btn-secondary w-full px-2 py-1.5 text-xs" type="submit">Retry fetch</button></form>
                                @endif
                                @if(!in_array($source->status, ['rejected', 'imported'], true))
                                    <form method="post" action="{{ route('admin.imports.reject', $source) }}" class="mt-2">@csrf<input type="hidden" name="note" value="Rejected during review."><button class="btn btn-secondary w-full px-2 py-1.5 text-xs text-red-700" type="submit">Reject</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-sm text-ink-500">No records in this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $sources->links() }}</div>

    <div class="mt-6 rounded-lg border border-ink-200 bg-white p-4 text-xs leading-5 text-ink-500">
        Approval creates a draft product with no copied competitor description and no approved price. Specifications must be verified from manufacturer documentation. Source descriptions are retained internally for reference only and are never automatically published.
    </div>
@endsection
