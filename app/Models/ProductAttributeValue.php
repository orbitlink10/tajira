<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:4',
            'value_boolean' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    public function displayValue(): ?string
    {
        if ($this->attributeValue) {
            return $this->attributeValue->value;
        }

        if ($this->value_text !== null) {
            return $this->value_text;
        }

        if ($this->value_boolean !== null) {
            return $this->value_boolean ? 'Yes' : 'No';
        }

        if ($this->value_number !== null) {
            $number = rtrim(rtrim((string) $this->value_number, '0'), '.');

            return $number;
        }

        return null;
    }
}
