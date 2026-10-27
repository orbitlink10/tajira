<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_indexable' => 'boolean',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories')
            ->withPivot(['is_primary', 'position'])
            ->withTimestamps();
    }

    public function publishedProducts(): BelongsToMany
    {
        return $this->products()->published();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function ancestors(): array
    {
        $ancestors = [];
        $category = $this->parent;
        $guard = 0;

        while ($category && $guard < 10) {
            array_unshift($ancestors, $category);
            $category = $category->parent;
            $guard++;
        }

        return $ancestors;
    }

    public function publishedProductCountDeep(): int
    {
        $ids = $this->descendantIds();

        return Product::query()
            ->published()
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $ids))
            ->count();
    }

    public function descendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: $this->name.' in Kenya | '.config('tajira.business.name');
    }

    public function seoDescription(): string
    {
        return $this->meta_description
            ?: Str::limit(strip_tags($this->description ?: 'Shop '.$this->name.' from Tajira in Kenya.'), 155);
    }
}
