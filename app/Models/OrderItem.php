<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'unit_price_minor' => 'integer',
            'quantity' => 'integer',
            'line_subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'tax_minor' => 'integer',
            'line_total_minor' => 'integer',
            'tax_inclusive' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function formattedUnitPrice(): string
    {
        return Money::format($this->unit_price_minor, $this->order?->currency ?? 'KES');
    }

    public function formattedTotal(): string
    {
        return Money::format($this->line_total_minor, $this->order?->currency ?? 'KES');
    }
}
