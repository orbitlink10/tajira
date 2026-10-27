<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Services\AuditLogger;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('reviews.moderate');

        $reviews = ProductReview::query()
            ->with('product:id,name,slug')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function approve(ProductReview $review, ReviewService $reviews, AuditLogger $audit)
    {
        $this->authorize('reviews.moderate');
        $reviews->approve($review, request()->user());
        $audit->log('review.approved', $review);

        return back()->with('success', 'Review approved and product rating recalculated.');
    }

    public function reject(ProductReview $review, ReviewService $reviews, AuditLogger $audit)
    {
        $this->authorize('reviews.moderate');
        $reviews->reject($review, request()->user());
        $audit->log('review.rejected', $review);

        return back()->with('success', 'Review rejected.');
    }

    public function destroy(ProductReview $review, ReviewService $reviews, AuditLogger $audit)
    {
        $this->authorize('reviews.moderate');
        $audit->log('review.deleted', $review, $review->toArray());
        $reviews->delete($review);

        return back()->with('success', 'Review deleted and rating recalculated.');
    }
}
