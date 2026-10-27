<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchService
{
    private const EFFECTIVE_PRICE_SQL = 'CASE
        WHEN sale_price_minor IS NOT NULL AND sale_price_minor < price_minor
             AND (sale_starts_at IS NULL OR sale_starts_at <= NOW())
             AND (sale_ends_at IS NULL OR sale_ends_at >= NOW())
        THEN sale_price_minor ELSE price_minor END';

    public function normalize(string $value): string
    {
        $value = Str::lower(trim($value));

        return preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;
    }

    public function results(array $filters = [], array $categoryIds = [], ?int $brandId = null, int $perPage = 24): LengthAwarePaginator
    {
        $query = Product::query()
            ->published()
            ->with([
                'brand:id,name,slug',
                'primaryCategory:id,name,slug',
                'primaryImage',
                'activeVariants',
                'attributeValues.attribute',
                'attributeValues.attributeValue',
            ]);

        if ($categoryIds !== []) {
            $query->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds));
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        $keyword = trim((string) ($filters['q'] ?? ''));

        if ($keyword !== '') {
            $this->applyKeyword($query, $keyword);
        }

        $brandSlugs = array_values(array_filter((array) ($filters['brand'] ?? [])));

        if ($brandSlugs !== []) {
            $query->whereHas('brand', fn (Builder $q) => $q->whereIn('slug', $brandSlugs));
        }

        if (! empty($filters['price_min']) && is_numeric($filters['price_min'])) {
            $query->whereRaw(self::EFFECTIVE_PRICE_SQL.' >= ?', [(int) round(((float) $filters['price_min']) * 100)]);
        }

        if (! empty($filters['price_max']) && is_numeric($filters['price_max'])) {
            $query->whereRaw(self::EFFECTIVE_PRICE_SQL.' <= ?', [(int) round(((float) $filters['price_max']) * 100)]);
        }

        if (($filters['availability'] ?? null) === 'in_stock') {
            $query->where(function (Builder $q) {
                $q->whereColumn('stock_quantity', '>', 'reserved_quantity')->orWhere('allow_oversell', true);
            });
        }

        $attributes = (array) ($filters['attr'] ?? []);

        foreach ($attributes as $attributeSlug => $values) {
            $values = array_values(array_filter((array) $values));

            if ($values === []) {
                continue;
            }

            $attribute = Attribute::query()->where('slug', $attributeSlug)->first();

            if (! $attribute) {
                continue;
            }

            $query->whereHas('attributeValues', function (Builder $valueQuery) use ($attribute, $values) {
                $valueQuery->where('attribute_id', $attribute->id)
                    ->where(function (Builder $inner) use ($values) {
                        $inner->whereHas('attributeValue', fn (Builder $q) => $q->whereIn('slug', $values))
                            ->orWhereIn('value_text', $values);

                        if (in_array('yes', $values, true) || in_array('1', $values, true)) {
                            $inner->orWhere('value_boolean', true);
                        }

                        if (in_array('no', $values, true) || in_array('0', $values, true)) {
                            $inner->orWhere('value_boolean', false);
                        }
                    });
            });
        }

        $numericFilters = (array) ($filters['attr_min'] ?? []);

        foreach ($numericFilters as $attributeSlug => $minimum) {
            if (! is_numeric($minimum)) {
                continue;
            }

            $attribute = Attribute::query()->where('slug', $attributeSlug)->first();

            if (! $attribute) {
                continue;
            }

            $query->whereHas('attributeValues', fn (Builder $q) => $q
                ->where('attribute_id', $attribute->id)
                ->where('value_number', '>=', (float) $minimum));
        }

        $this->applySort($query, $filters, $keyword);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<int, array{slug:string,name:string,type:string,unit:?string,values:array,min:?float,max:?float}>
     */
    public function filterGroups(array $categoryIds = [], ?int $brandId = null, array $filters = []): array
    {
        $base = Product::query()->published();

        if ($categoryIds !== []) {
            $base->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds));
        }

        if ($brandId) {
            $base->where('brand_id', $brandId);
        }

        if (! empty($filters['q'])) {
            $this->applyKeyword($base, (string) $filters['q']);
        }

        $productIds = (clone $base)->select('products.id');

        $rows = DB::table('product_attribute_values as pav')
            ->join('attributes as a', 'a.id', '=', 'pav.attribute_id')
            ->leftJoin('attribute_values as av', 'av.id', '=', 'pav.attribute_value_id')
            ->whereIn('pav.product_id', $productIds)
            ->where('a.is_filterable', true)
            ->select([
                'a.id as attribute_id',
                'a.name as attribute_name',
                'a.slug as attribute_slug',
                'a.type as attribute_type',
                'a.unit as attribute_unit',
                'a.position as attribute_position',
                'av.slug as value_slug',
                'av.value as value_label',
                'av.position as value_position',
                'pav.value_text',
                'pav.value_number',
                'pav.value_boolean',
            ])
            ->selectRaw('COUNT(DISTINCT pav.product_id) as product_count')
            ->groupBy([
                'a.id', 'a.name', 'a.slug', 'a.type', 'a.unit', 'a.position',
                'av.slug', 'av.value', 'av.position', 'pav.value_text', 'pav.value_number', 'pav.value_boolean',
            ])
            ->orderBy('a.position')
            ->orderBy('av.position')
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $slug = $row->attribute_slug;

            if (! isset($groups[$slug])) {
                $groups[$slug] = [
                    'slug' => $slug,
                    'name' => $row->attribute_name,
                    'type' => $row->attribute_type,
                    'unit' => $row->attribute_unit,
                    'values' => [],
                    'min' => null,
                    'max' => null,
                ];
            }

            if ($row->attribute_type === 'number') {
                if ($row->value_number !== null) {
                    $groups[$slug]['min'] = $groups[$slug]['min'] === null ? (float) $row->value_number : min($groups[$slug]['min'], (float) $row->value_number);
                    $groups[$slug]['max'] = $groups[$slug]['max'] === null ? (float) $row->value_number : max($groups[$slug]['max'], (float) $row->value_number);
                }

                continue;
            }

            $valueSlug = $row->value_slug ?: Str::slug((string) ($row->value_text ?? ($row->value_boolean ? 'yes' : 'no')));
            $valueLabel = $row->value_label ?: ($row->value_text ?? ($row->value_boolean ? 'Yes' : 'No'));

            if ($valueSlug === '' || isset($groups[$slug]['values'][$valueSlug])) {
                if (isset($groups[$slug]['values'][$valueSlug])) {
                    $groups[$slug]['values'][$valueSlug]['count'] += (int) $row->product_count;
                }

                continue;
            }

            $groups[$slug]['values'][$valueSlug] = [
                'slug' => $valueSlug,
                'label' => $valueLabel,
                'count' => (int) $row->product_count,
            ];
        }

        return array_values($groups);
    }

    /**
     * @return array{products:array,categories:array,brands:array}
     */
    public function autocomplete(string $term, int $limit = 8): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2) {
            return ['products' => [], 'categories' => [], 'brands' => []];
        }

        $products = Product::query()
            ->published()
            ->with(['brand:id,name', 'primaryImage'])
            ->where(function (Builder $q) use ($term) {
                $like = '%'.$term.'%';
                $q->where('sku', 'like', $like)
                    ->orWhere('model', 'like', $like)
                    ->orWhere('mpn', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('search_terms', 'like', $like)
                    ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like))
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('product_aliases')
                            ->whereColumn('product_aliases.product_id', 'products.id')
                            ->where('product_aliases.normalized', 'like', '%'.$this->normalize($term).'%');
                    });
            })
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'url' => route('products.show', $product->slug),
                'price' => $product->formattedPrice(),
                'image' => $product->imageUrl(),
                'brand' => $product->brand?->name,
            ])
            ->all();

        $categories = Category::query()
            ->where('is_active', true)
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('position')
            ->limit(4)
            ->get()
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'url' => route('categories.show', $category->slug),
            ])
            ->all();

        $brands = Brand::query()
            ->where('is_active', true)
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->limit(4)
            ->get()
            ->map(fn (Brand $brand) => [
                'name' => $brand->name,
                'url' => route('brands.show', $brand->slug),
            ])
            ->all();

        return ['products' => $products, 'categories' => $categories, 'brands' => $brands];
    }

    private function applyKeyword(Builder $query, string $keyword): void
    {
        $like = '%'.$keyword.'%';
        $normalized = '%'.$this->normalize($keyword).'%';
        $exact = Str::lower($keyword);

        $query->where(function (Builder $q) use ($like, $normalized) {
            $q->where('sku', 'like', $like)
                ->orWhere('model', 'like', $like)
                ->orWhere('mpn', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('search_terms', 'like', $like)
                ->orWhere('short_description', 'like', $like)
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like))
                ->orWhereExists(function ($sub) use ($normalized) {
                    $sub->select(DB::raw(1))
                        ->from('product_aliases')
                        ->whereColumn('product_aliases.product_id', 'products.id')
                        ->where('product_aliases.normalized', 'like', $normalized);
                });
        });

        $query->select('products.*')
            ->selectRaw(
                'CASE
                    WHEN LOWER(sku) = ? THEN 1200
                    WHEN LOWER(model) = ? THEN 1100
                    WHEN LOWER(mpn) = ? THEN 1100
                    WHEN LOWER(name) LIKE ? THEN 800
                    WHEN name LIKE ? THEN 600
                    WHEN model LIKE ? THEN 550
                    WHEN search_terms LIKE ? THEN 450
                    ELSE 100
                END as relevance',
                [$exact, $exact, $exact, $exact.'%', $like, $like, $like]
            );
    }

    private function applySort(Builder $query, array $filters, string $keyword): void
    {
        $sort = $filters['sort'] ?? 'relevance';

        match ($sort) {
            'price_asc' => $query->whereNotNull('price_minor')->orderByRaw(self::EFFECTIVE_PRICE_SQL.' ASC')->orderBy('name'),
            'price_desc' => $query->whereNotNull('price_minor')->orderByRaw(self::EFFECTIVE_PRICE_SQL.' DESC')->orderBy('name'),
            'newest' => $query->orderByDesc('published_at')->orderByDesc('id'),
            'name' => $query->orderBy('name'),
            default => $keyword !== ''
                ? $query->orderByDesc('relevance')->orderBy('name')
                : $query->orderByDesc('is_featured')->orderByDesc('published_at')->orderBy('name'),
        };
    }
}
