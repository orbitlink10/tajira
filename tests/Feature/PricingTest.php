<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_tax_inclusive_and_exclusive_lines_are_calculated_without_floats(): void
    {
        $pricing = app(PricingService::class);

        $inclusive = $this->product(['price_minor' => 116000, 'tax_inclusive' => true]);
        $line = $pricing->lineTotals($inclusive, null, 1);
        $this->assertSame(116000, $line['total']);
        $this->assertSame(16000, $line['tax']);

        $exclusive = $this->product(['price_minor' => 100000, 'tax_inclusive' => false]);
        $line = $pricing->lineTotals($exclusive, null, 1);
        $this->assertSame(116000, $line['total']);
        $this->assertSame(16000, $line['tax']);
    }

    public function test_coupons_apply_percentage_caps_and_minimums(): void
    {
        $pricing = app(PricingService::class);
        $coupon = Coupon::query()->create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 1000,
            'min_subtotal_minor' => 50000,
            'max_discount_minor' => 8000,
            'is_active' => true,
        ]);

        $this->assertSame(8000, $pricing->couponDiscount($coupon, 100000));
        $this->assertSame(0, $pricing->couponDiscount($coupon, 40000));
        $this->assertSame(5000, $pricing->couponDiscount($coupon, 50000));
    }

    public function test_cart_totals_allocate_discount_and_tax_across_lines(): void
    {
        $pricing = app(PricingService::class);
        $first = $this->product(['price_minor' => 100000, 'tax_inclusive' => true]);
        $second = $this->product(['price_minor' => 200000, 'tax_inclusive' => false]);

        $coupon = Coupon::query()->create([
            'code' => 'FIXED50',
            'type' => 'fixed',
            'value' => 50000,
            'is_active' => true,
        ]);

        $totals = $pricing->cartTotals([
            ['product' => $first, 'variant' => null, 'quantity' => 1],
            ['product' => $second, 'variant' => null, 'quantity' => 1],
        ], $coupon, 10000);

        $this->assertSame(300000, $totals['subtotal']);
        $this->assertSame(50000, $totals['discount']);
        $this->assertSame(50000, collect($totals['lines'])->sum('discount'));
        $this->assertGreaterThan(0, $totals['tax']);
        $this->assertSame($totals['total'], collect($totals['lines'])->sum('total') + 10000);
    }
}
