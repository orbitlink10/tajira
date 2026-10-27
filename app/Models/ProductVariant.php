<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'price_minor' => 'integer',
            'sale_price_minor' => 'integer',
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function availableStock(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ($this->product?->allow_oversell || $this->availableStock() > 0);
    }

    public function effectivePriceMinor(): ?int
    {
        return $this->sale_price_minor ?? $this->price_minor ?? $this->product?->effectivePriceMinor();
    }

    public function attributesLabel(): string
    {
        if (empty($this->attributes)) {
            return $this->name;
        }

        return collect($this->attributes)
            ->map(fn ($value, $key) => ucfirst($key).': '.$value)
            ->implode(' · ');
    }
}
