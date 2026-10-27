<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;

class ReviewService
{
    public function approve(ProductReview $review, User $moderator): void
    {
        $review->update([
            'status' => ProductReview::STATUS_APPROVED,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        $this->recalculate($review->product_id);
    }

    public function reject(ProductReview $review, User $moderator): void
    {
        $review->update([
            'status' => ProductReview::STATUS_REJECTED,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        $this->recalculate($review->product_id);
    }

    public function delete(ProductReview $review): void
    {
        $productId = $review->product_id;
        $review->delete();
        $this->recalculate($productId);
    }

    public function recalculate(int $productId): void
    {
        $approved = ProductReview::query()
            ->where('product_id', $productId)
            ->where('status', ProductReview::STATUS_APPROVED);

        $count = (clone $approved)->count();
        $average = $count > 0 ? (float) (clone $approved)->avg('rating') : 0;

        Product::query()->whereKey($productId)->update([
            'rating_count' => $count,
            'rating_average' => round($average, 2),
        ]);
    }
}
