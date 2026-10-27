<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'min_order_minor' => 'integer',
            'max_order_minor' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function formattedPrice(): string
    {
        $minor = $this->getAttribute('effective_price_minor') ?? $this->price_minor;

        return $minor === 0 ? 'Free' : Money::format((int) $minor);
    }

    public function appliesTo(int $subtotalMinor): bool
    {
        if ($this->min_order_minor !== null && $subtotalMinor < $this->min_order_minor) {
            return false;
        }

        if ($this->max_order_minor !== null && $subtotalMinor > $this->max_order_minor) {
            return false;
        }

        return true;
    }
}
