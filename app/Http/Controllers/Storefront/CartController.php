<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PricingService $pricing,
        private readonly CouponService $coupons,
    ) {}

    public function index()
    {
        $items = $this->cart->items();
        $coupon = null;

        if ($code = session('coupon_code')) {
            try {
                $coupon = $this->coupons->findValid($code, $this->cart->subtotalMinor(), auth()->user(), auth()->user()?->email);
            } catch (ValidationException $exception) {
                session()->forget('coupon_code');
                session()->flash('error', $exception->getMessage());
            }
        }

        $lines = $items->map(fn ($item) => [
            'product' => $item['product'],
            'variant' => $item['variant'],
            'quantity' => $item['quantity'],
        ])->all();

        $totals = $this->pricing->cartTotals($lines, $coupon, 0);
        $meta = [
            'title' => 'Your cart | '.setting('business.name', 'Tajira'),
            'description' => 'Review the items in your Tajira cart.',
            'canonical' => route('cart.index'),
            'robots' => 'noindex,nofollow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => null,
        ];

        return view('storefront.cart.index', compact('items', 'totals', 'coupon', 'meta'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()->with('activeVariants')->findOrFail($data['product_id']);
        abort_unless($product->isPublished(), 404);

        if (! $product->isPurchasable()) {
            return redirect()->route('quotes.create', ['product' => $product->id])
                ->with('info', 'This product is priced by quotation. Tell us what you need and we will respond.');
        }

        $this->cart->add($product, (int) ($data['quantity'] ?? 1), $data['variant_id'] ?? null);

        return back()->with('success', $product->name.' was added to your cart.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        foreach ($data['items'] as $key => $quantity) {
            $this->cart->update((string) $key, (int) $quantity);
        }

        return redirect()->route('cart.index')->with('success', 'Your cart was updated.');
    }

    public function remove(Request $request)
    {
        $data = $request->validate(['key' => ['required', 'string']]);
        $this->cart->remove($data['key']);

        return redirect()->route('cart.index')->with('success', 'Item removed from your cart.');
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['coupon_code' => ['required', 'string', 'max:40']]);

        $this->coupons->findValid($data['coupon_code'], $this->cart->subtotalMinor(), $request->user(), $request->user()?->email);
        session(['coupon_code' => strtoupper(trim($data['coupon_code']))]);

        return redirect()->route('cart.index')->with('success', 'Coupon applied.');
    }

    public function removeCoupon()
    {
        session()->forget('coupon_code');

        return redirect()->route('cart.index')->with('success', 'Coupon removed.');
    }
}
