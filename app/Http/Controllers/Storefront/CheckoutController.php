<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\ShippingMethod;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\CouponService;
use App\Services\Payments\PaymentManager;
use App\Services\PricingService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PricingService $pricing,
        private readonly CouponService $coupons,
        private readonly ShippingService $shipping,
        private readonly CheckoutService $checkout,
        private readonly PaymentManager $payments,
    ) {}

    public function index(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('info', 'Your cart is empty.');
        }

        $items = $this->cart->items();
        $county = old('county', $request->user()?->addresses()->where('is_default', true)->first()?->county ?? 'Nairobi City');
        $deliveryType = old('delivery_type', 'delivery');
        $subtotal = $items->sum('line_total_minor');
        $coupon = $this->resolveCoupon($subtotal);
        $methods = $this->shipping->methodsFor($county, $subtotal, $deliveryType);
        $totals = $this->pricing->cartTotals($this->lines($items), $coupon, 0);
        $paymentMethods = $this->payments->enabledMethods();

        if ($paymentMethods === []) {
            $paymentMethods = ['manual' => 'Arrange payment with our team'];
        }

        $checkoutToken = session('checkout_token') ?: Str::random(40);
        session(['checkout_token' => $checkoutToken]);

        $meta = $this->privateMeta('Checkout', route('checkout.index'));

        return view('storefront.checkout.index', compact(
            'items', 'totals', 'coupon', 'methods', 'paymentMethods', 'county', 'deliveryType', 'checkoutToken', 'meta'
        ));
    }

    public function shipping(Request $request, ShippingService $shipping)
    {
        $data = $request->validate([
            'county' => ['required', 'string', 'max:80'],
            'delivery_type' => ['nullable', Rule::in(['delivery', 'pickup'])],
        ]);

        $subtotal = $this->cart->subtotalMinor();
        $methods = $shipping->methodsFor($data['county'], $subtotal, $data['delivery_type'] ?? 'delivery')
            ->map(fn (ShippingMethod $method) => [
                'id' => $method->id,
                'name' => $method->name,
                'price' => $method->formattedPrice(),
                'price_minor' => (int) $method->getAttribute('effective_price_minor'),
                'lead_time' => $method->lead_time,
                'type' => $method->type,
            ])
            ->values();

        return response()->json(['methods' => $methods])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:140'],
            'customer_email' => ['required', 'email', 'max:180'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s()-]{9,20}$/'],
            'company' => ['nullable', 'string', 'max:180'],
            'county' => ['required', Rule::in(counties())],
            'town' => ['required', 'string', 'max:120'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'delivery_notes' => ['nullable', 'string', 'max:500'],
            'delivery_type' => ['required', Rule::in(['delivery', 'pickup'])],
            'shipping_method_id' => ['nullable', 'integer', 'exists:shipping_methods,id'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'string', 'max:30'],
            'checkout_token' => ['required', 'string'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        if (! hash_equals((string) session('checkout_token'), (string) $data['checkout_token'])) {
            throw ValidationException::withMessages([
                'checkout' => 'Your checkout session expired. Please review your details and try again.',
            ]);
        }

        $enabled = array_keys($this->payments->enabledMethods());

        if ($enabled === []) {
            $enabled = ['manual'];
        }

        if (! in_array($data['payment_method'], $enabled, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Choose an available payment method.',
            ]);
        }

        if ($data['delivery_type'] === 'pickup') {
            $data['address_line'] = filled($data['address_line'] ?? null) ? $data['address_line'] : 'Pickup';
            $data['shipping_method_id'] = null;
        } elseif (empty($data['address_line'])) {
            throw ValidationException::withMessages(['address_line' => 'Enter the delivery address.']);
        }

        $lines = $this->cart->payloadForCheckout();
        $data['ip'] = $request->ip();
        $data['coupon_code'] = session('coupon_code');

        $order = $this->checkout->placeOrder($data, $lines, $request->user(), (string) $request->session()->getId());

        $request->session()->put('order_access.'.$order->number, true);
        $request->session()->forget('checkout_token');
        $this->cart->clear();

        $this->checkout->sendConfirmation($order);

        if ($data['payment_method'] === 'manual') {
            return redirect()->route('orders.show', $order->number)
                ->with('success', 'Order received. Our team will contact you to arrange payment.');
        }

        try {
            $result = $this->payments->initiate($order, $data['payment_method'], ['phone' => $data['customer_phone']]);
        } catch (ValidationException $exception) {
            return redirect()->route('payments.show', $order->number)
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect($result['redirect'] ?? route('payments.show', $order->number))
            ->with('success', $result['message']);
    }

    private function resolveCoupon(int $subtotal): ?Coupon
    {
        $code = session('coupon_code');

        if (! $code) {
            return null;
        }

        try {
            return $this->coupons->findValid($code, $subtotal, auth()->user(), auth()->user()?->email);
        } catch (ValidationException $exception) {
            session()->forget('coupon_code');

            return null;
        }
    }

    private function lines($items): array
    {
        return $items->map(fn ($item) => [
            'product' => $item['product'],
            'variant' => $item['variant'],
            'quantity' => $item['quantity'],
        ])->all();
    }

    private function privateMeta(string $title, string $canonical): array
    {
        return [
            'title' => $title.' | '.setting('business.name', 'Tajira'),
            'description' => 'Secure Tajira checkout.',
            'canonical' => $canonical,
            'robots' => 'noindex,nofollow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => null,
        ];
    }
}
