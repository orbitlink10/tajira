<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'rating' => 4,
            'title' => 'Good product',
            'body' => 'A genuine test review body for moderation tests.',
            'status' => ProductReview::STATUS_PENDING,
        ];
    }
}
