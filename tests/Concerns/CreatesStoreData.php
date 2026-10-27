<?php

namespace Tests\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Support\Str;

trait CreatesStoreData
{
    protected function admin(string $role = User::ROLE_SUPER_ADMIN): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
            'mfa_enabled' => false,
        ]);
    }

    protected function product(array $overrides = []): Product
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create(array_merge([
            'brand_id' => $brand->id,
            'primary_category_id' => $category->id,
            'price_minor' => 100000,
            'stock_quantity' => 10,
            'reserved_quantity' => 0,
        ], $overrides));

        $product->categories()->sync([$category->id => ['is_primary' => true, 'position' => 0]]);

        return $product->fresh();
    }

    protected function addToCart(Product $product, int $quantity = 1): void
    {
        app(CartService::class)->add($product->fresh(), $quantity);
    }

    protected function enablePickup(): ShippingMethod
    {
        return ShippingMethod::query()->firstOrCreate(
            ['code' => 'test-pickup'],
            [
                'name' => 'Test pickup',
                'type' => 'pickup',
                'price_minor' => 0,
                'lead_time' => 'Test pickup',
                'is_active' => true,
            ]
        );
    }

    protected function checkoutData(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer-'.Str::lower(Str::random(6)).'@example.test',
            'customer_phone' => '0712345678',
            'company' => null,
            'county' => 'Nairobi City',
            'town' => 'Nairobi',
            'address_line' => 'Test address',
            'postal_code' => '00100',
            'delivery_notes' => null,
            'delivery_type' => 'pickup',
            'shipping_method_id' => null,
            'coupon_code' => null,
            'notes' => null,
            'payment_method' => 'manual',
            'ip' => '127.0.0.1',
        ], $overrides);
    }
}
