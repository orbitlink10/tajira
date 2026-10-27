<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = 'Test '.fake()->unique()->words(3, true).' '.Str::upper(Str::random(4));

        return [
            'brand_id' => Brand::factory(),
            'primary_category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'sku' => 'TST-'.Str::upper(Str::random(8)),
            'model' => 'MOD-'.Str::upper(Str::random(5)),
            'product_type' => 'simple',
            'status' => Product::STATUS_PUBLISHED,
            'published_at' => now(),
            'short_description' => 'A test product used by automated tests.',
            'description' => '<p>Test product description.</p>',
            'price_minor' => 100000,
            'stock_quantity' => 10,
            'reserved_quantity' => 0,
            'stock_status' => 'in_stock',
            'is_indexable' => true,
            'meta_title' => Str::title($name).' | Tajira',
            'meta_description' => 'Test meta description for '.$name,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => Product::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function quoteOnly(): static
    {
        return $this->state(fn () => [
            'price_minor' => null,
            'price_on_request' => true,
        ]);
    }
}
