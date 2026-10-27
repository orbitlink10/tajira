<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const KEY = 'cart.items';

    public function raw(): array
    {
        return Session::get(self::KEY, []);
    }

    public function add(Product $product, int $quantity = 1, ?int $variantId = null): void
    {
        if (! $product->isPublished()) {
            throw ValidationException::withMessages(['quantity' => 'This product is not available.']);
        }

        $variant = null;

        if ($product->product_type === 'variant' || $variantId) {
            $variant = $product->activeVariants()->find($variantId);

            if (! $variant) {
                throw ValidationException::withMessages(['variant_id' => 'Choose a valid product variant.']);
            }
        }

        if (! $product->isPurchasable()) {
            throw ValidationException::withMessages(['quantity' => 'This product is available by quotation only.']);
        }

        $quantity = max(1, min(99, $quantity));
        $key = $this->key($product->id, $variant?->id);
        $items = $this->raw();
        $existing = $items[$key]['quantity'] ?? 0;
        $desired = min(99, $existing + $quantity);
        $available = $variant ? $variant->availableStock() : $product->availableStock();

        if (! $product->allow_oversell && $desired > $available) {
            throw ValidationException::withMessages([
                'quantity' => $available > 0
                    ? 'Only '.$available.' unit(s) are currently available.'
                    : 'This product is currently out of stock.',
            ]);
        }

        $items[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $desired,
        ];

        Session::put(self::KEY, $items);
    }

    public function update(string $key, int $quantity): void
    {
        $items = $this->raw();

        if (! isset($items[$key])) {
            return;
        }

        if ($quantity <= 0) {
            unset($items[$key]);
            Session::put(self::KEY, $items);

            return;
        }

        $items[$key]['quantity'] = max(1, min(99, $quantity));
        Session::put(self::KEY, $items);
    }

    public function remove(string $key): void
    {
        $items = $this->raw();
        unset($items[$key]);
        Session::put(self::KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
        Session::forget('coupon_code');
    }

    public function count(): int
    {
        return array_sum(array_column($this->raw(), 'quantity'));
    }

    /**
     * @return Collection<int, array{key:string,product:Product,variant:?ProductVariant,quantity:int,unit_price_minor:?int,line_total_minor:int,available:int}>
     */
    public function items(): Collection
    {
        $raw = $this->raw();

        if (empty($raw)) {
            return collect();
        }

        $products = Product::query()
            ->published()
            ->with(['brand', 'primaryCategory', 'primaryImage', 'activeVariants'])
            ->whereIn('id', array_column($raw, 'product_id'))
            ->get()
            ->keyBy('id');

        $items = collect();

        foreach ($raw as $key => $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                continue;
            }

            $variant = $item['variant_id']
                ? $product->activeVariants->firstWhere('id', $item['variant_id'])
                : null;

            if ($item['variant_id'] && ! $variant) {
                continue;
            }

            $unit = $product->effectivePriceMinor($variant);
            $quantity = max(1, (int) $item['quantity']);
            $available = $variant ? $variant->availableStock() : $product->availableStock();

            $items->push([
                'key' => $key,
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price_minor' => $unit,
                'line_total_minor' => ($unit ?? 0) * $quantity,
                'available' => $available,
            ]);
        }

        return $items;
    }

    public function subtotalMinor(): int
    {
        return (int) $this->items()->sum('line_total_minor');
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function key(int $productId, ?int $variantId = null): string
    {
        return $productId.'_'.($variantId ?? '0');
    }

    public function payloadForCheckout(): array
    {
        return $this->items()->map(fn ($item) => [
            'key' => $item['key'],
            'product' => $item['product'],
            'variant' => $item['variant'],
            'quantity' => $item['quantity'],
        ])->all();
    }
}
