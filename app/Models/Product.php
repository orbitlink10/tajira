<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'key_features' => 'array',
            'applications' => 'array',
            'package_contents' => 'array',
            'price_minor' => 'integer',
            'sale_price_minor' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'approved_at' => 'datetime',
            'price_on_request' => 'boolean',
            'tax_inclusive' => 'boolean',
            'allow_oversell' => 'boolean',
            'is_featured' => 'boolean',
            'is_indexable' => 'boolean',
            'rating_average' => 'float',
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
            ->withPivot(['is_primary', 'position'])
            ->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('position');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderByDesc('is_primary')->orderBy('position');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class);
    }

    public function productRelations(): HasMany
    {
        return $this->hasMany(ProductRelation::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot(['type', 'position'])
            ->wherePivot('type', 'related')
            ->orderBy('product_relations.position');
    }

    public function accessories(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot(['type', 'position'])
            ->wherePivot('type', 'accessory')
            ->orderBy('product_relations.position');
    }

    public function compatibleProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot(['type', 'position'])
            ->wherePivot('type', 'compatible')
            ->orderBy('product_relations.position');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)->orderBy('position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved')->latest();
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ProductAlias::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->published()
            ->where('is_indexable', true)
            ->where(fn (Builder $q) => $q->whereNull('robots')->orWhere('robots', 'not like', '%noindex%'));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '>', 'reserved_quantity');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function isOnSale(): bool
    {
        if ($this->sale_price_minor === null || $this->sale_price_minor >= ($this->price_minor ?? PHP_INT_MAX)) {
            return false;
        }

        $now = now();

        if ($this->sale_starts_at && $this->sale_starts_at->isFuture()) {
            return false;
        }

        if ($this->sale_ends_at && $this->sale_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function effectivePriceMinor(?ProductVariant $variant = null): ?int
    {
        if ($variant) {
            if ($variant->sale_price_minor !== null) {
                return $variant->sale_price_minor;
            }

            if ($variant->price_minor !== null) {
                return $variant->price_minor;
            }
        }

        if ($this->price_on_request || $this->price_minor === null) {
            return null;
        }

        return $this->isOnSale() ? $this->sale_price_minor : $this->price_minor;
    }

    public function listPriceMinor(): ?int
    {
        return $this->price_minor;
    }

    public function availableStock(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    public function isAvailable(): bool
    {
        return $this->allow_oversell || $this->availableStock() > 0;
    }

    public function stockLabel(): string
    {
        if ($this->price_on_request) {
            return 'Priced by quotation';
        }

        if ($this->allow_oversell) {
            return 'Available to order';
        }

        if ($this->availableStock() > 0) {
            return $this->availableStock() <= max(1, $this->low_stock_threshold)
                ? 'Low stock ('.$this->availableStock().' left)'
                : 'In stock';
        }

        return $this->variants->contains(fn (ProductVariant $v) => $v->is_active && $v->availableStock() > 0)
            ? 'Select a variant'
            : 'Out of stock';
    }

    public function taxInclusive(): bool
    {
        return $this->tax_inclusive ?? (bool) config('tajira.tax.inclusive_by_default');
    }

    public function formattedPrice(): string
    {
        $price = $this->effectivePriceMinor();

        return $price === null ? 'Request price' : Money::format($price);
    }

    public function imageUrl(): string
    {
        $path = $this->primaryImage?->path ?? $this->og_image_path;

        return $path ? asset('storage/'.ltrim($path, '/')) : asset('images/product-placeholder.svg');
    }

    public function seoTitle(): string
    {
        return $this->meta_title
            ?: $this->name.' | '.config('tajira.business.name');
    }

    public function seoDescription(): string
    {
        return $this->meta_description
            ?: Str::limit(strip_tags($this->short_description ?: $this->description ?: ''), 155);
    }

    public function canonicalUrl(): string
    {
        return $this->canonical_override ?: route('products.show', $this);
    }

    public function isIndexable(): bool
    {
        return $this->is_indexable && $this->isPublished();
    }

    public function isPurchasable(): bool
    {
        return ! $this->price_on_request && $this->effectivePriceMinor() !== null;
    }

    public function specificationRows(): array
    {
        return $this->attributeValues
            ->sortBy(fn (ProductAttributeValue $value) => $value->attribute?->position ?? 0)
            ->map(function (ProductAttributeValue $value) {
                return [
                    'label' => $value->attribute?->name,
                    'value' => $value->displayValue(),
                    'unit' => $value->unit ?: $value->attribute?->unit,
                ];
            })
            ->filter(fn ($row) => filled($row['label']) && filled($row['value']))
            ->values()
            ->all();
    }
}
