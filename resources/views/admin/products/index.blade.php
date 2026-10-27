@extends('layouts.admin')

@section('title', 'Products')
@section('heading', 'Products')
@section('subheading', 'Catalogue, pricing, stock, verification and publication.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.products.index') }}" class="badge {{ !request('status') ? 'bg-ink-900 text-white' : 'bg-white text-ink-700 border border-ink-200' }}">All</a>
            @foreach(['draft' => 'Draft', 'pending_review' => 'Review', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived'] as $key => $label)
                <a href="{{ route('admin.products.index', ['status' => $key]) }}" class="badge {{ request('status') === $key ? 'bg-ink-900 text-white' : 'bg-white text-ink-700 border border-ink-200' }}">{{ $label }} ({{ $statusCounts[$key] ?? 0 }})</a>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-2">
            @can('catalogue.manage')
                <a href="{{ route('admin.products.import.form') }}" class="btn btn-secondary">CSV import</a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">New product</a>
            @endcan
            <a href="{{ route('admin.products.export') }}" class="btn btn-secondary">Export CSV</a>
        </div>
    </div>

    @can('catalogue.manage')
        <form id="bulk-products-form" method="post" action="{{ route('admin.products.bulk-status') }}" class="card mb-4 flex flex-wrap items-end gap-3 p-4" onsubmit="return confirm('Apply this bulk action? Product publication guardrails still apply.');">
            @csrf
            <div class="flex-1">
                <label for="bulk-action" class="field-label">Bulk action on selected products</label>
                <select id="bulk-action" name="action" class="field-input max-w-xs">
                    <option value="draft">Move to draft</option>
                    <option value="publish">Publish (guardrails apply)</option>
                    <option value="archive">Archive</option>
                </select>
            </div>
            <button class="btn btn-secondary" type="submit">Apply to selected</button>
            <p class="w-full text-xs text-ink-500">Select products with the checkboxes below. Publishing skips incomplete products and reports why.</p>
        </form>
    @endcan

    <form method="get" action="{{ route('admin.products.index') }}" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-[220px] flex-1">
            <label for="q" class="field-label">Search</label>
            <input id="q" name="q" value="{{ request('q') }}" class="field-input" placeholder="Name, SKU, model or MPN">
        </div>
        <div>
            <label for="brand" class="field-label">Brand</label>
            <select id="brand" name="brand" class="field-input">
                <option value="">All brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->slug }}" @selected(request('brand') === $brand->slug)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field-input">
                <option value="">All statuses</option>
                @foreach(['draft' => 'Draft', 'pending_review' => 'Pending review', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived'] as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-secondary">Apply</button>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-ink-600 underline">Reset</a>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th class="w-8"><span class="sr-only">Select</span></th><th>Product</th><th>Brand</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $product->id }}" form="bulk-products-form" class="h-4 w-4 rounded border-ink-300" aria-label="Select {{ $product->name }}"></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <img src="{{ $product->imageUrl() }}" alt="" width="36" height="36" loading="lazy" class="h-9 w-9 rounded border border-ink-100 object-contain bg-white">
                                    <div>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-ink-900 hover:text-brand-700">{{ $product->name }}</a>
                                        <p class="text-xs text-ink-400">{{ $product->primaryCategory?->name ?: 'No primary category' }} · {{ $product->model ?: 'No model' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $product->brand?->name ?: '—' }}</td>
                            <td class="text-xs">{{ $product->sku }}</td>
                            <td class="whitespace-nowrap">{{ $product->price_on_request ? 'Quote only' : ($product->price_minor !== null ? money($product->effectivePriceMinor()) : '—') }}</td>
                            <td>{{ $product->stock_quantity }} <span class="text-xs text-ink-400">/ {{ $product->reserved_quantity }} reserved</span></td>
                            <td><span class="badge {{ $product->status === 'published' ? 'bg-emerald-50 text-emerald-800' : ($product->status === 'archived' ? 'bg-ink-100 text-ink-600' : 'bg-amber-50 text-amber-900') }}">{{ ucwords(str_replace('_', ' ', $product->status)) }}</span></td>
                            <td class="text-xs text-ink-500">{{ $product->updated_at->diffForHumans() }}</td>
                            <td class="whitespace-nowrap">
                                <a class="text-xs font-semibold text-brand-700" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                                <a class="ml-2 text-xs font-semibold text-ink-500" href="{{ route('admin.products.preview', $product) }}" target="_blank">Preview</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-sm text-ink-500">No products match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $products->links() }}</div>
@endsection
