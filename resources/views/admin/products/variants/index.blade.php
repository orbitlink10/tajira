@extends('layouts.admin')

@section('title', 'Variants')
@section('heading', $product->name)
@section('subheading', 'Variants and stock options.')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.products.edit', $product) }}" class="text-sm font-semibold text-brand-700">← Back to product</a>
        <a href="{{ route('admin.products.variants.create', $product) }}" class="btn btn-primary">New variant</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Name</th><th>SKU</th><th>Options</th><th>Price</th><th>Stock</th><th>Default</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($variants as $variant)
                        <tr>
                            <td class="font-semibold text-ink-900">{{ $variant->name }}</td>
                            <td class="text-xs">{{ $variant->sku }}</td>
                            <td class="text-xs">{{ $variant->attributesLabel() }}</td>
                            <td>{{ $variant->effectivePriceMinor() !== null ? money($variant->effectivePriceMinor()) : '—' }}</td>
                            <td>{{ $variant->stock_quantity }} <span class="text-xs text-ink-400">/ {{ $variant->reserved_quantity }} reserved</span></td>
                            <td>{{ $variant->is_default ? 'Yes' : 'No' }}</td>
                            <td>{{ $variant->is_active ? 'Yes' : 'No' }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.products.variants.edit', [$product, $variant]) }}" class="text-xs font-semibold text-brand-700">Edit</a>
                                <form method="post" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" class="inline" onsubmit="return confirm('Delete this variant?');">
                                    @csrf @method('delete')
                                    <button class="ml-2 text-xs font-semibold text-red-700" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-sm text-ink-500">No variants yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
