<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\SkuGenerator;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function index(Product $product)
    {
        $this->authorize('catalogue.view');
        $variants = $product->variants()->withCount([])->get();

        return view('admin.products.variants.index', compact('product', 'variants'));
    }

    public function create(Product $product)
    {
        $this->authorize('catalogue.manage');

        return view('admin.products.variants.form', ['product' => $product, 'variant' => new ProductVariant(['is_active' => true, 'stock_quantity' => 0]), 'optionsText' => '']);
    }

    public function store(Request $request, Product $product, SkuGenerator $skus, AuditLogger $audit, InventoryService $inventory)
    {
        $this->authorize('catalogue.manage');
        $data = $this->validated($request, $product, null, $skus);
        $data['attributes'] = $this->parseOptions($request->input('options_text'));

        if ($request->boolean('is_default')) {
            $product->variants()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $variant = $product->variants()->create($data);
        $product->update(['product_type' => 'variant']);
        $inventory->syncStockStatus($product->fresh());
        $audit->log('product.variant.created', $product, [], ['variant_id' => $variant->id]);

        return redirect()->route('admin.products.variants.index', $product)->with('success', 'Variant created.');
    }

    public function edit(Product $product, ProductVariant $variant)
    {
        $this->authorize('catalogue.manage');
        abort_unless($variant->product_id === $product->id, 404);
        $optionsText = collect($variant->attributes ?? [])->map(fn ($value, $key) => $key.': '.$value)->implode("\n");

        return view('admin.products.variants.form', compact('product', 'variant', 'optionsText'));
    }

    public function update(Request $request, Product $product, ProductVariant $variant, SkuGenerator $skus, AuditLogger $audit, InventoryService $inventory)
    {
        $this->authorize('catalogue.manage');
        abort_unless($variant->product_id === $product->id, 404);
        $data = $this->validated($request, $product, $variant, $skus);
        $data['attributes'] = $this->parseOptions($request->input('options_text'));

        if ($request->boolean('is_default')) {
            $product->variants()->whereKeyNot($variant->id)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $variant->update($data);
        $inventory->syncStockStatus($product->fresh());
        $audit->log('product.variant.updated', $product, [], ['variant_id' => $variant->id]);

        return redirect()->route('admin.products.variants.index', $product)->with('success', 'Variant updated.');
    }

    public function destroy(Product $product, ProductVariant $variant, AuditLogger $audit, InventoryService $inventory)
    {
        $this->authorize('catalogue.manage');
        abort_unless($variant->product_id === $product->id, 404);

        if (OrderItem::query()->where('variant_id', $variant->id)->exists()) {
            return back()->with('error', 'This variant is referenced by orders. Deactivate it instead of deleting it.');
        }

        $audit->log('product.variant.deleted', $product, ['variant_id' => $variant->id]);
        $variant->delete();
        $inventory->syncStockStatus($product->fresh());

        return back()->with('success', 'Variant deleted.');
    }

    private function validated(Request $request, Product $product, ?ProductVariant $variant, SkuGenerator $skus): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('product_variants', 'sku')->ignore($variant?->id)],
            'mpn' => ['nullable', 'string', 'max:160'],
            'gtin' => ['nullable', 'string', 'max:32'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'options_text' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['sku'] = $data['sku'] ?: $skus->forVariant($product->sku, $data['name']);
        $data['price_minor'] = Money::parseToMinor($request->input('price'));
        $data['sale_price_minor'] = Money::parseToMinor($request->input('sale_price'));
        $data['stock_quantity'] = (int) ($data['stock_quantity'] ?? 0);
        $data['position'] = (int) ($data['position'] ?? 0);
        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');
        unset($data['price'], $data['sale_price'], $data['options_text']);

        return $data;
    }

    private function parseOptions(?string $text): array
    {
        $options = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key !== '' && $value !== '') {
                $options[$key] = $value;
            }
        }

        return $options;
    }
}
