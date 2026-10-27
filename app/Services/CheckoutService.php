<?php

namespace App\Services;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly ShippingService $shipping,
        private readonly CouponService $coupons,
        private readonly InventoryService $inventory,
        private readonly OrderNumberService $numbers,
    ) {}

    /**
     * @param  array<int, array{product:Product,variant:?ProductVariant,quantity:int}>  $items
     */
    public function placeOrder(array $data, array $items, ?User $user = null, ?string $lockKey = null): Order
    {
        if (empty($items)) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        $lockKey = 'checkout:'.($lockKey ?: ($user?->id ?? 'guest'));

        try {
            return Cache::lock($lockKey, 20)->block(8, function () use ($data, $items, $user) {
                return DB::transaction(function () use ($data, $items, $user) {
                    $lockedItems = $this->lockAndRefreshItems($items);
                    $subtotal = 0;

                    foreach ($lockedItems as $item) {
                        $subtotal += $this->pricing->lineSubtotal($item['product'], $item['variant'], $item['quantity']);
                    }

                    if ($subtotal <= 0) {
                        throw ValidationException::withMessages(['cart' => 'Your cart contains products without an approved price.']);
                    }

                    $coupon = null;

                    if (! empty($data['coupon_code'])) {
                        $coupon = $this->coupons->findValid(
                            $data['coupon_code'],
                            $subtotal,
                            $user,
                            $data['customer_email'] ?? null
                        );
                    }

                    $deliveryType = ($data['delivery_type'] ?? 'delivery') === 'pickup' ? 'pickup' : 'delivery';
                    $shipping = $this->shipping->resolve(
                        isset($data['shipping_method_id']) ? (int) $data['shipping_method_id'] : null,
                        $data['county'] ?? '',
                        $subtotal,
                        $deliveryType
                    );

                    $totals = $this->pricing->cartTotals($lockedItems, $coupon, $shipping['price_minor']);
                    $taxRateBp = $this->pricing->taxRateBasisPoints();
                    $order = Order::query()->create([
                        'number' => $this->numbers->order(),
                        'user_id' => $user?->id,
                        'status' => Order::STATUS_PENDING_PAYMENT,
                        'payment_status' => 'pending',
                        'currency' => config('tajira.currency', 'KES'),
                        'subtotal_minor' => $totals['subtotal'],
                        'discount_minor' => $totals['discount'],
                        'shipping_minor' => $totals['shipping'],
                        'tax_minor' => $totals['tax'],
                        'total_minor' => $totals['total'],
                        'coupon_code' => $coupon?->code,
                        'customer_name' => $data['customer_name'],
                        'customer_email' => strtolower($data['customer_email']),
                        'customer_phone' => $data['customer_phone'],
                        'company' => $data['company'] ?? null,
                        'county' => $data['county'],
                        'town' => $data['town'],
                        'address_line' => $data['address_line'],
                        'postal_code' => $data['postal_code'] ?? null,
                        'delivery_notes' => $data['delivery_notes'] ?? null,
                        'delivery_type' => $deliveryType,
                        'shipping_zone_id' => $shipping['method']->shipping_zone_id,
                        'shipping_method_id' => $shipping['method']->id,
                        'shipping_method_name' => $shipping['method']->name,
                        'shipping_lead_time' => $shipping['lead_time'],
                        'tax_inclusive' => collect($totals['lines'])->every(fn ($line) => $line['tax_inclusive']),
                        'tax_rate_bp' => $taxRateBp,
                        'payment_method' => $data['payment_method'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'placed_at' => now(),
                        'ip_hash' => isset($data['ip']) ? hash('sha256', $data['ip'].'|'.config('app.key')) : null,
                    ]);

                    foreach ($totals['lines'] as $line) {
                        /** @var Product $product */
                        $product = $line['product'];
                        $variant = $line['variant'];

                        OrderItem::query()->create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'variant_id' => $variant?->id,
                            'name' => $variant ? $product->name.' — '.$variant->name : $product->name,
                            'sku' => $variant?->sku ?? $product->sku,
                            'brand_name' => $product->brand?->name,
                            'unit_price_minor' => $line['unit'],
                            'quantity' => $line['quantity'],
                            'line_subtotal_minor' => $line['subtotal'],
                            'discount_minor' => $line['discount'],
                            'tax_minor' => $line['tax'],
                            'line_total_minor' => $line['total'],
                            'tax_inclusive' => $line['tax_inclusive'],
                            'attributes' => $variant?->attributes,
                            'image_path' => $product->primaryImage?->path,
                        ]);
                    }

                    $this->inventory->reserveForOrder($order, $lockedItems);

                    if ($coupon) {
                        $this->coupons->record($coupon, $order->id, $totals['discount'], $user, $data['customer_email']);
                    }

                    OrderStatusHistory::query()->create([
                        'order_id' => $order->id,
                        'status' => Order::STATUS_PENDING_PAYMENT,
                        'note' => 'Order placed. Stock reserved for '.setting('checkout.reservation_minutes', 30).' minutes pending payment.',
                        'user_id' => $user?->id,
                        'created_at' => now(),
                    ]);

                    return $order->load(['items', 'payments']);
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'checkout' => 'Your checkout is already being processed. Please wait a moment and check your orders.',
            ]);
        }
    }

    public function sendConfirmation(Order $order): void
    {
        if (config('mail.default') === 'log' && app()->environment('local')) {
            Mail::to($order->customer_email)->queue(new OrderConfirmation($order->loadMissing('items')));

            return;
        }

        Mail::to($order->customer_email)->queue(new OrderConfirmation($order->loadMissing('items')));
    }

    /**
     * @param  array<int, array{product:Product,variant:?ProductVariant,quantity:int}>  $items
     * @return array<int, array{product:Product,variant:?ProductVariant,quantity:int}>
     */
    private function lockAndRefreshItems(array $items): array
    {
        $locked = [];

        foreach ($items as $item) {
            $product = Product::query()
                ->with(['brand', 'primaryImage'])
                ->whereKey($item['product']->id)
                ->lockForUpdate()
                ->first();

            if (! $product || ! $product->isPublished() || ! $product->isPurchasable()) {
                throw ValidationException::withMessages([
                    'cart' => 'One of the products in your cart is no longer available. Please review your cart.',
                ]);
            }

            $variant = null;

            if ($item['variant'] ?? null) {
                $variant = ProductVariant::query()
                    ->whereKey($item['variant']->id)
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $variant) {
                    throw ValidationException::withMessages([
                        'cart' => $product->name.' has changed. Please review your cart.',
                    ]);
                }
            }

            $quantity = max(1, min(99, (int) $item['quantity']));
            $available = $variant ? $variant->availableStock() : $product->availableStock();

            if (! $product->allow_oversell && $quantity > $available) {
                throw ValidationException::withMessages([
                    'cart' => 'Only '.$available.' unit(s) of '.$product->name.' are available.',
                ]);
            }

            $locked[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
            ];
        }

        return $locked;
    }
}
