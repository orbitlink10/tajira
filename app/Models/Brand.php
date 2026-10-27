<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_indexable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function publishedProducts(): HasMany
    {
        return $this->products()->published();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: $this->name.' Products in Kenya | '.config('tajira.business.name');
    }

    public function seoDescription(): string
    {
        return $this->meta_description
            ?: Str::limit(strip_tags($this->description ?: 'Browse genuine '.$this->name.' networking products from Tajira.'), 155);
    }
}
