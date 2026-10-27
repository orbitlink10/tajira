<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Services\CouponService;
use App\Services\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShippingCouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_methods_are_limited_by_county_and_minimum_order(): void
    {
        $zone = ShippingZone::query()->create([
            'name' => 'Nairobi',
            'counties' => ['Nairobi City'],
            'is_active' => true,
        ]);

        $method = ShippingMethod::query()->create([
            'shipping_zone_id' => $zone->id,
            'name' => 'Nairobi delivery',
            'code' => 'nairobi-delivery',
            'type' => 'flat',
            'price_minor' => 50000,
            'min_order_minor' => 100000,
            'is_active' => true,
        ]);

        $service = app(ShippingService::class);

        $available = $service->methodsFor('Nairobi City', 150000);
        $this->assertTrue($available->contains('id', $method->id));

        $this->assertTrue($service->methodsFor('Mombasa', 150000)->isEmpty());
        $this->assertTrue($service->methodsFor('Nairobi City', 50000)->isEmpty());
    }

    public function test_invalid_shipping_method_choice_is_rejected_server_side(): void
    {
        $this->expectException(ValidationException::class);

        app(ShippingService::class)->resolve(99999, 'Nairobi City', 150000, 'delivery');
    }

    public function test_coupon_validity_and_percentage_calculation(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'TEN',
            'type' => 'percentage',
            'value' => 1000,
            'min_subtotal_minor' => 50000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $service = app(CouponService::class);
        $this->assertSame($coupon->id, $service->findValid('ten', 100000)->id);

        $this->expectException(ValidationException::class);
        $service->findValid('TEN', 10000);
    }
}
