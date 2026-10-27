<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'shipping_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'tax_inclusive' => 'boolean',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'stock_committed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_PAYMENT, self::STATUS_PAID, self::STATUS_PROCESSING], true);
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total_minor, $this->currency);
    }

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function paymentStatusLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->payment_status));
    }

    public function canBeViewedBy(?User $user): bool
    {
        if ($user?->isAdmin()) {
            return true;
        }

        return $user && $this->user_id === $user->id;
    }

    public function latestPayment(): ?Payment
    {
        return $this->payments()->latest()->first();
    }
}
