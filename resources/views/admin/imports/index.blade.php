@extends('layouts.admin')

@section('title', 'Catalogue imports')
@section('heading', 'Catalogue discovery and reconciliation')
@section('subheading', 'Staged source observations. Nothing is published automatically.')

@section('content')
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Unique discovered URLs</p><p class="mt-1 text-2xl font-bold">{{ number_format($metrics['total_discovered']) }}</p></div>
        <div class="card p-4"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Staged as draft products</p><p class="mt-1 text-2xl font-bold">{{ number_format($metrics['imported']) }}</p><p class="text-xs text-ink-500">{{ $metrics['pending_verification'] }} awaiting verification</p></div>
        <div class="card p-4"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Duplicates flagged</p><p class="mt-1 text-2xl font-bold">{{ number_format($metrics['duplicates']) }}</p></div>
        <div class="card p-4"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Unresolved / failed</p><p class="mt-1 text-2xl font-bold">{{ number_format($metrics['unresolved']) }}</p><p class="text-xs text-ink-500">{{ $metrics['failed'] }} failed fetch{{ $metrics['failed'] === 1 ? '' : 'es' }}</p></div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Start a discovery batch</h2>
            <p class="mt-1 text-xs leading-5 text-ink-500">Reads the approved source's public product sitemap, stages unique URLs, and stores discovery timestamps. It does not fetch product pages yet. Respects robots.txt, host allowlists, size limits and the configured crawl rate. A CSV import is available on the products screen if discovery is blocked.</p>
            <form method="post" action="{{ route('admin.imports.discovery') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="limit" class="field-label">Maximum URLs to stage</label>
                    <input id="limit" type="number" name="limit" min="1" max="1000" value="{{ old('limit', 100) }}" required class="field-input">
                </div>
                <div>
                    <label for="source" class="field-label">Source base URL (optional)</label>
                    <input id="source" name="source" value="{{ old('source', config('tajira.imports.source_base')) }}" class="field-input">
                </div>
                <div class="sm:col-span-2">
                    <button class="btn btn-primary" type="submit">Stage sitemap URLs</button>
                    <a href="{{ route('admin.products.import.form') }}" class="btn btn-secondary ml-2">Import owner CSV</a>
                </div>
            </form>
            <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Competitor prices and stock are recorded only as reference observations. They are never used as Tajira selling prices or stock. Imported products start as drafts and cannot be published without approved pricing and verified content.</p>
        </section>

        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Every discovery record</h2>
            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                @foreach(['discovered' => 'Discovered', 'needs_review' => 'Needs review', 'imported' => 'Draft created', 'duplicate' => 'Duplicate flagged', 'failed' => 'Failed', 'rejected' => 'Rejected'] as $key => $label)
                    <div class="rounded-lg border border-ink-200 p-3">
                        <dt class="text-xs text-ink-500">{{ $label }}</dt>
                        <dd class="text-xl font-bold text-ink-900">{{ $metrics['status_counts'][$key] ?? 0 }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-xs text-ink-500">A staged record is not proof of verification. Unknown specifications remain unknown and supplier/manufacturer sources must be checked before publication.</p>
        </section>
    </div>

    <section class="mt-6 card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-ink-200 px-4 py-3">
            <h2 class="text-sm font-bold text-ink-900">Discovery batches</h2>
            <span class="text-xs text-ink-500">Resumable checkpoints are stored per batch</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Reference</th><th>Status</th><th>Progress</th><th>Created</th><th>Finished</th><th></th></tr></thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $batch->reference }}<span class="block text-xs font-normal text-ink-400">{{ ucfirst($batch->type) }} · {{ $batch->source }}</span></td>
                            <td><span class="badge bg-ink-100 text-ink-700">{{ $batch->statusLabel() }}</span></td>
                            <td class="text-xs">{{ $batch->processed_rows }}/{{ $batch->total_rows }} processed<span class="block text-ink-400">{{ $batch->imported_rows }} drafts · {{ $batch->duplicate_rows }} duplicates · {{ $batch->failed_rows }} failed</span></td>
                            <td class="text-xs text-ink-500">{{ $batch->created_at->format('j M Y, H:i') }}</td>
                            <td class="text-xs text-ink-500">{{ $batch->finished_at?->format('j M Y, H:i') ?: '—' }}</td>
                            <td><a href="{{ route('admin.imports.show', $batch) }}" class="text-xs font-semibold text-brand-700">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-sm text-ink-500">No discovery batches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Coverage by brand (staged)</h2>
            <div class="mt-3 max-h-80 overflow-y-auto">
                <table class="table-admin">
                    <thead><tr><th>Brand</th><th>Discovered</th><th>Draft created</th></tr></thead>
                    <tbody>
                        @forelse($metrics['brand_coverage'] as $row)
                            <tr><td>{{ $row->raw_brand }}</td><td>{{ $row->discovered }}</td><td>{{ $row->imported }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-sm text-ink-500">No brand observations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="card p-5">
            <h2 class="text-sm font-bold text-ink-900">Coverage by source category</h2>
            <div class="mt-3 max-h-80 overflow-y-auto">
                <table class="table-admin">
                    <thead><tr><th>Source category</th><th>Discovered</th><th>Draft created</th></tr></thead>
                    <tbody>
                        @forelse($metrics['category_coverage'] as $name => $row)
                            <tr><td>{{ $name }}</td><td>{{ $row['discovered'] }}</td><td>{{ $row['imported'] }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-sm text-ink-500">No category observations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="mt-6 card overflow-hidden">
        <div class="border-b border-ink-200 px-4 py-3"><h2 class="text-sm font-bold text-ink-900">Recent staging records needing a decision</h2></div>
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Source product</th><th>Brand / model</th><th>Status</th><th>Observed</th><th>Source</th></tr></thead>
                <tbody>
                    @forelse($staged as $source)
                        <tr>
                            <td class="max-w-sm"><span class="font-medium text-ink-900">{{ $source->raw_name ?: 'Unnamed' }}</span><span class="block text-xs text-ink-400">SKU proposal: {{ $source->proposed_sku ?: '—' }}</span></td>
                            <td class="text-xs">{{ $source->raw_brand ?: '—' }}<span class="block text-ink-400">{{ $source->raw_model ?: '—' }}</span></td>
                            <td><span class="badge bg-ink-100 text-ink-700">{{ $source->statusLabel() }}</span></td>
                            <td class="text-xs text-ink-500">{{ $source->raw_price_minor !== null ? money($source->raw_price_minor).' (reference only)' : '—' }}</td>
                            <td><a href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-brand-700">View source ↗</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-sm text-ink-500">No records awaiting a decision.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
