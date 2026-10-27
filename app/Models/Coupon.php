<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_subtotal_minor' => 'integer',
            'max_discount_minor' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'per_customer_limit' => 'integer',
            'applies_to' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isPercentage(): bool
    {
        return $this->type === 'percentage';
    }

    public function displayValue(): string
    {
        return $this->isPercentage()
            ? rtrim(rtrim(number_format($this->value / 100, 2, '.', ''), '0'), '.').'%'
            : Money::format($this->value);
    }
}
