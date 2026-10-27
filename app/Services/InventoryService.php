<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function reserveForOrder(Order $order, array $items): void
    {
        $minutes = (int) setting('checkout.reservation_minutes', config('tajira.checkout.reservation_minutes', 30));
        $expiresAt = now()->addMinutes(max(5, $minutes));

        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product'];
            $variant = $item['variant'] ?? null;
            $quantity = (int) $item['quantity'];

            if ($variant) {
                $lockedVariant = ProductVariant::query()->whereKey($variant->id)->lockForUpdate()->first();

                if (! $lockedVariant || ! $lockedVariant->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => $product->name.' is no longer available. Please update your cart.',
                    ]);
                }

                $available = max(0, $lockedVariant->stock_quantity - $lockedVariant->reserved_quantity);

                if (! $product->allow_oversell && $quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => 'Only '.$available.' unit(s) of '.$product->name.' ('.$lockedVariant->name.') are available.',
                    ]);
                }

                $lockedVariant->increment('reserved_quantity', $quantity);
            } else {
                $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->first();

                if (! $lockedProduct || ! $lockedProduct->isPublished()) {
                    throw ValidationException::withMessages([
                        'cart' => $product->name.' is no longer available. Please update your cart.',
                    ]);
                }

                $available = max(0, $lockedProduct->stock_quantity - $lockedProduct->reserved_quantity);

                if (! $lockedProduct->allow_oversell && $quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => 'Only '.$available.' unit(s) of '.$lockedProduct->name.' are available.',
                    ]);
                }

                $lockedProduct->increment('reserved_quantity', $quantity);
            }

            StockReservation::query()->create([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'order_id' => $order->id,
                'quantity' => $quantity,
                'status' => 'active',
                'expires_at' => $expiresAt,
            ]);
        }
    }

    public function commitForOrder(Order $order): void
    {
        if ($order->stock_committed_at !== null) {
            return;
        }

        $reservations = StockReservation::query()
            ->where('order_id', $order->id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            if ($reservation->variant_id) {
                $variant = ProductVariant::query()->whereKey($reservation->variant_id)->lockForUpdate()->first();

                if ($variant) {
                    $variant->stock_quantity = max(0, $variant->stock_quantity - $reservation->quantity);
                    $variant->reserved_quantity = max(0, $variant->reserved_quantity - $reservation->quantity);
                    $variant->save();
                }
            }

            $product = Product::query()->whereKey($reservation->product_id)->lockForUpdate()->first();

            if ($product) {
                if ($reservation->variant_id === null) {
                    $product->stock_quantity = max(0, $product->stock_quantity - $reservation->quantity);
                }

                $product->reserved_quantity = max(0, $product->reserved_quantity - ($reservation->variant_id === null ? $reservation->quantity : 0));
                $product->save();
                $this->syncStockStatus($product);
            }

            $reservation->update(['status' => 'committed']);

            DB::table('inventory_movements')->insert([
                'product_id' => $reservation->product_id,
                'variant_id' => $reservation->variant_id,
                'type' => 'sale',
                'quantity' => -$reservation->quantity,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'note' => 'Order '.$order->number,
                'user_id' => null,
                'created_at' => now(),
            ]);
        }

        $order->forceFill(['stock_committed_at' => now()])->save();
    }

    public function releaseForOrder(Order $order, string $reason = 'released'): void
    {
        $reservations = StockReservation::query()
            ->where('order_id', $order->id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            if ($reservation->variant_id) {
                $variant = ProductVariant::query()->whereKey($reservation->variant_id)->lockForUpdate()->first();
                $variant?->decrement('reserved_quantity', min($reservation->quantity, $variant->reserved_quantity));
            } else {
                $product = Product::query()->whereKey($reservation->product_id)->lockForUpdate()->first();

                if ($product) {
                    $product->reserved_quantity = max(0, $product->reserved_quantity - $reservation->quantity);
                    $product->save();
                    $this->syncStockStatus($product);
                }
            }

            $reservation->update(['status' => $reason]);
        }
    }

    public function releaseExpired(): int
    {
        $count = 0;

        StockReservation::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use (&$count) {
                foreach ($reservations as $reservation) {
                    DB::transaction(function () use ($reservation, &$count) {
                        $fresh = StockReservation::query()->whereKey($reservation->id)->lockForUpdate()->first();

                        if (! $fresh || $fresh->status !== 'active') {
                            return;
                        }

                        $order = Order::query()->whereKey($fresh->order_id)->lockForUpdate()->first();

                        $this->releaseForOrder($order, 'expired');
                        $count++;

                        if ($order && $order->status === Order::STATUS_PENDING_PAYMENT) {
                            $order->update([
                                'status' => Order::STATUS_CANCELLED,
                                'payment_status' => 'failed',
                                'cancelled_at' => now(),
                            ]);

                            OrderStatusHistory::query()->create([
                                'order_id' => $order->id,
                                'status' => Order::STATUS_CANCELLED,
                                'note' => 'Stock reservation expired before payment was confirmed.',
                                'created_at' => now(),
                            ]);
                        }
                    });
                }
            });

        return $count;
    }

    public function adjust(Product $product, int $delta, ?string $note = null, ?User $user = null, ?ProductVariant $variant = null): void
    {
        DB::transaction(function () use ($product, $delta, $note, $user, $variant) {
            if ($variant) {
                $locked = ProductVariant::query()->whereKey($variant->id)->lockForUpdate()->firstOrFail();
                $new = $locked->stock_quantity + $delta;

                if ($new < 0) {
                    throw ValidationException::withMessages(['quantity' => 'Stock cannot be negative.']);
                }

                $locked->update(['stock_quantity' => $new]);
            } else {
                $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $new = $locked->stock_quantity + $delta;

                if ($new < 0) {
                    throw ValidationException::withMessages(['quantity' => 'Stock cannot be negative.']);
                }

                $locked->update(['stock_quantity' => $new]);
                $this->syncStockStatus($locked);
            }

            DB::table('inventory_movements')->insert([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'type' => 'adjustment',
                'quantity' => $delta,
                'reference_type' => null,
                'reference_id' => null,
                'note' => $note,
                'user_id' => $user?->id,
                'created_at' => now(),
            ]);
        });
    }

    public function syncStockStatus(Product $product): void
    {
        $available = $product->stock_quantity - $product->reserved_quantity;
        $variantsAvailable = $product->activeVariants()->whereColumn('stock_quantity', '>', 'reserved_quantity')->exists();

        $status = match (true) {
            $product->allow_oversell => 'in_stock',
            $available > 0 => 'in_stock',
            $product->product_type === 'variant' && $variantsAvailable => 'in_stock',
            default => 'out_of_stock',
        };

        if ($status === 'in_stock' && $product->low_stock_threshold > 0 && $available > 0 && $available <= $product->low_stock_threshold) {
            $status = 'low_stock';
        }

        Product::query()->whereKey($product->id)->update(['stock_status' => $status]);
    }
}
