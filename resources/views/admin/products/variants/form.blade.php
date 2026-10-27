@extends('layouts.admin')

@section('title', $variant->exists ? 'Edit variant' : 'New variant')
@section('heading', $variant->exists ? 'Edit variant' : 'New variant')
@section('subheading', $product->name)

@section('content')
    <form method="post" action="{{ $variant->exists ? route('admin.products.variants.update', [$product, $variant]) : route('admin.products.variants.store', $product) }}" class="max-w-2xl space-y-4">
        @csrf
        @if($variant->exists) @method('put') @endif

        <section class="card p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="field-label">Variant name</label>
                    <input id="name" name="name" value="{{ old('name', $variant->name) }}" required class="field-input" placeholder="e.g. 8-port, 16-port, white">
                </div>
                <div>
                    <label for="sku" class="field-label">SKU</label>
                    <input id="sku" name="sku" value="{{ old('sku', $variant->sku) }}" class="field-input" placeholder="Auto-generated if blank">
                </div>
                <div>
                    <label for="mpn" class="field-label">MPN</label>
                    <input id="mpn" name="mpn" value="{{ old('mpn', $variant->mpn) }}" class="field-input">
                </div>
                <div>
                    <label for="price" class="field-label">Price (KES) — blank uses product price</label>
                    <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $variant->price_minor !== null ? number_format($variant->price_minor / 100, 2, '.', '') : '') }}" class="field-input">
                </div>
                <div>
                    <label for="sale_price" class="field-label">Sale price (KES)</label>
                    <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $variant->sale_price_minor !== null ? number_format($variant->sale_price_minor / 100, 2, '.', '') : '') }}" class="field-input">
                </div>
                <div>
                    <label for="stock_quantity" class="field-label">Stock on hand</label>
                    <input id="stock_quantity" name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', $variant->stock_quantity) }}" class="field-input">
                </div>
                <div>
                    <label for="weight_grams" class="field-label">Weight (g)</label>
                    <input id="weight_grams" name="weight_grams" type="number" min="0" value="{{ old('weight_grams', $variant->weight_grams) }}" class="field-input">
                </div>
                <div>
                    <label for="position" class="field-label">Position</label>
                    <input id="position" name="position" type="number" min="0" value="{{ old('position', $variant->position) }}" class="field-input">
                </div>
                <div class="sm:col-span-2">
                    <label for="options_text" class="field-label">Options (one “key: value” per line)</label>
                    <textarea id="options_text" name="options_text" rows="4" class="field-input font-mono text-xs">{{ old('options_text', $optionsText) }}</textarea>
                </div>
                <div class="flex flex-wrap gap-4 sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded" @checked(old('is_default', $variant->is_default))> Default variant</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $variant->is_active))> Active</label>
                </div>
            </div>
        </section>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save variant</button>
            <a href="{{ route('admin.products.variants.index', $product) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
