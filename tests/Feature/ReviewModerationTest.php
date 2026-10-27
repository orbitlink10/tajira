<?php

namespace Tests\Feature;

use App\Models\ProductReview;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class ReviewModerationTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_pending_reviews_are_not_published_and_ratings_update_only_after_moderation(): void
    {
        $product = $this->product();
        $review = ProductReview::factory()->create([
            'product_id' => $product->id,
            'rating' => 5,
            'body' => 'This pending review body should not appear yet.',
            'status' => ProductReview::STATUS_PENDING,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('This pending review body should not appear yet.');

        app(ReviewService::class)->approve($review, $this->admin());

        $product->refresh();
        $this->assertSame(1, $product->rating_count);
        $this->assertSame(5.0, $product->rating_average);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('This pending review body should not appear yet.');
    }

    public function test_rejecting_a_review_recalculates_the_rating(): void
    {
        $product = $this->product();
        $review = ProductReview::factory()->create([
            'product_id' => $product->id,
            'rating' => 4,
            'status' => ProductReview::STATUS_APPROVED,
        ]);
        app(ReviewService::class)->recalculate($product->id);
        $this->assertSame(1, $product->fresh()->rating_count);

        app(ReviewService::class)->reject($review, $this->admin());

        $this->assertSame(0, $product->fresh()->rating_count);
        $this->assertSame(0.0, $product->fresh()->rating_average);
    }
}
