<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()->wishlistItems()
            ->with(['product' => fn ($query) => $query->published()->with(['brand:id,name', 'primaryImage'])])
            ->latest()
            ->get()
            ->filter(fn ($item) => $item->product !== null);

        $meta = [
            'title' => 'Wishlist | '.setting('business.name', 'Tajira'),
            'description' => 'Products saved to your Tajira wishlist.',
            'canonical' => route('account.wishlist'),
            'robots' => 'noindex,nofollow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => null,
        ];

        return view('storefront.account.wishlist', compact('items', 'meta'));
    }

    public function toggle(Request $request, Product $product)
    {
        abort_unless($product->isPublished(), 404);

        $existing = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return back()->with('success', 'Removed from your wishlist.');
        }

        Wishlist::query()->create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return back()->with('success', 'Saved to your wishlist.');
    }

    public function destroy(Request $request, Product $product)
    {
        Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        return back()->with('success', 'Removed from your wishlist.');
    }
}
