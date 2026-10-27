<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;

class PricingService
{
    public function taxRateBasisPoints(): int
    {
        return (int) round((float) setting('tax.rate_percent', config('tajira.tax.rate_percent', 16)) * 100);
    }

    public function taxInclusiveDefault(): bool
    {
        return (bool) setting('tax.inclusive', config('tajira.tax.inclusive_by_default', true));
    }

    public function lineSubtotal(Product $product, ?ProductVariant $variant, int $quantity): int
    {
        $unit = $product->effectivePriceMinor($variant);

        if ($unit === null) {
            return 0;
        }

        return $unit * max(1, $quantity);
    }

    /**
     * @return array{unit:int,quantity:int,subtotal:int,discount:int,tax:int,total:int,tax_inclusive:bool}
     */
    public function lineTotals(
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        int $allocatedDiscountMinor = 0,
        ?int $taxRateBp = null
    ): array {
        $quantity = max(1, $quantity);
        $unit = $product->effectivePriceMinor($variant) ?? 0;
        $subtotal = $unit * $quantity;
        $discount = min($allocatedDiscountMinor, $subtotal);
        $net = $subtotal - $discount;
        $inclusive = $product->taxInclusive();
        $rate = $taxRateBp ?? $this->taxRateBasisPoints();

        if ($inclusive) {
            $tax = $rate > 0 ? (int) round($net * $rate / (10000 + $rate)) : 0;
            $total = $net;
        } else {
            $tax = (int) round($net * $rate / 10000);
            $total = $net + $tax;
        }

        return [
            'unit' => $unit,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'tax_inclusive' => $inclusive,
        ];
    }

    /**
     * @param  array<int, array{product:Product,variant:?ProductVariant,quantity:int}>  $lines
     * @return array{subtotal:int,discount:int,tax:int,shipping:int,total:int,lines:array}
     */
    public function cartTotals(array $lines, ?Coupon $coupon = null, int $shippingMinor = 0): array
    {
        $subtotal = 0;

        foreach ($lines as $line) {
            $subtotal += $this->lineSubtotal($line['product'], $line['variant'], $line['quantity']);
        }

        $discount = $coupon ? $this->couponDiscount($coupon, $subtotal) : 0;
        $remainingDiscount = $discount;
        $lineResults = [];
        $tax = 0;
        $total = $shippingMinor;

        foreach ($lines as $index => $line) {
            $lineSubtotal = $this->lineSubtotal($line['product'], $line['variant'], $line['quantity']);
            $allocated = $index === array_key_last($lines)
                ? $remainingDiscount
                : ($subtotal > 0 ? (int) round($discount * $lineSubtotal / $subtotal) : 0);
            $allocated = min($allocated, $remainingDiscount);
            $remainingDiscount -= $allocated;

            $result = $this->lineTotals($line['product'], $line['variant'], $line['quantity'], $allocated);
            $tax += $result['tax'];
            $total += $result['total'];

            $lineResults[] = array_merge($result, [
                'product' => $line['product'],
                'variant' => $line['variant'],
            ]);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shippingMinor,
            'total' => max(0, $total),
            'lines' => $lineResults,
        ];
    }

    public function couponDiscount(Coupon $coupon, int $subtotalMinor): int
    {
        if ($coupon->min_subtotal_minor !== null && $subtotalMinor < $coupon->min_subtotal_minor) {
            return 0;
        }

        $discount = $coupon->isPercentage()
            ? (int) round($subtotalMinor * $coupon->value / 10000)
            : $coupon->value;

        if ($coupon->max_discount_minor !== null) {
            $discount = min($discount, $coupon->max_discount_minor);
        }

        return max(0, min($discount, $subtotalMinor));
    }
}
