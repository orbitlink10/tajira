@extends('layouts.admin')

@section('title', 'Products')
@section('heading', 'Products')
@section('subheading', 'Catalogue, pricing, stock, verification and publication.')

@section('content')
    <div class="tajira-products-heading"><div><h2 class="tajira-page-title">Products</h2><p>Manage and view all products available in the system</p></div>@can('catalogue.manage')<a class="btn btn-primary" href="{{ route('admin.products.create') }}">+ Add Product</a>@endcan</div>
    <details class="tajira-product-tools"><summary>Filters, import and bulk actions</summary><div class="mt-4">    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
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
        <form id="bulk-products-form" method="post" action="{{ route('admin.products.bulk-status') }}" class="card mb-4 flex flex-wrap items-end gap-3 p-4 tajira-bulk-tools" onsubmit="return confirm('Apply this bulk action? Product publication guardrails still apply.');">
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

</div></details>
    <div class="card overflow-hidden"><div class="tajira-product-list-head"><h2>Product List</h2><form method="get" action="{{ route('admin.products.index') }}"><label for="product-search" class="sr-only">Search products</label><input id="product-search" name="q" value="{{ request('q') }}" placeholder="Search by product name...">@if(request('brand'))<input type="hidden" name="brand" value="{{ request('brand') }}">@endif @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif<button class="btn btn-primary">Search</button></form></div>
        <div class="overflow-x-auto">
            <table class="table-admin tajira-product-table">
                <thead><tr><th>#</th><th>Image</th><th>Name</th><th>Price (KES)</th><th>Stock / Status</th><th>Category</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><span>{{ $products->firstItem() + $loop->index }}</span>@can('catalogue.manage')<input type="checkbox" name="ids[]" value="{{ $product->id }}" form="bulk-products-form" class="mt-2 block h-4 w-4 rounded border-ink-300" aria-label="Select {{ $product->name }}">@endcan</td>
                            <td><img class="tajira-product-image" src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" width="128" height="82"></td>
                            <td><a href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a><small class="tajira-product-detail">{{ $product->sku }} @if($product->brand) &middot; {{ $product->brand->name }} @endif</small></td>
                            <td class="whitespace-nowrap">{{ $product->formattedPrice() }}</td>
                            <td>{{ $product->stock_quantity }}<small class="tajira-product-detail">{{ $product->reserved_quantity }} reserved<br>{{ ucwords(str_replace('_', ' ', $product->status)) }}</small></td>
                            <td>{{ $product->primaryCategory?->name ?: 'Uncategorised' }}</td>
                            <td><div class="tajira-product-actions">
                                <a class="preview" href="{{ route('admin.products.preview', $product) }}" target="_blank" rel="noopener">Preview</a>
                                @can('catalogue.manage')
                                    <a class="update" href="{{ route('admin.products.edit', $product) }}">Update</a>
                                    <form method="post" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product? This is a soft delete.');">@csrf @method('delete')<button class="delete" type="submit">Delete</button></form>
                                @endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-sm text-ink-500">No products match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $products->links() }}</div>
@endsection
