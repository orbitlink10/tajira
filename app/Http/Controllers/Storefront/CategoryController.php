<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Services\SearchService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category, SearchService $search, SeoService $seo, StructuredDataService $structured)
    {
        abort_unless($category->is_active, 404);

        $category->load(['activeChildren' => fn ($query) => $query->withCount(['publishedProducts'])]);
        $categoryIds = $category->descendantIds();
        $filters = $this->filtersFromRequest($request);
        $products = $search->results($filters, $categoryIds, null, (int) config('tajira.seo.pagination_per_page', 24));
        $filterGroups = $search->filterGroups($categoryIds, null, $filters);
        $filterBrands = Brand::query()->where('is_active', true)->whereHas('publishedProducts', fn ($query) => $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds)))->withCount('publishedProducts')->orderBy('name')->get();

        $crumbs = [['name' => 'Home', 'url' => '/']];

        foreach ($category->ancestors() as $ancestor) {
            $crumbs[] = ['name' => $ancestor->name, 'url' => route('categories.show', $ancestor->slug)];
        }

        $crumbs[] = ['name' => $category->name, 'url' => route('categories.show', $category->slug)];

        $page = $products->currentPage();
        $hasFilters = $this->hasActiveFilters($filters);
        $meta = $seo->forCategory($category, $page);

        if ($hasFilters) {
            $meta['robots'] = 'noindex,follow';
            $meta['canonical'] = route('categories.show', $category->slug).($page > 1 ? '?page='.$page : '');
        }

        $jsonLd = [$structured->breadcrumbs($crumbs)];

        return view('storefront.categories.show', compact('category', 'products', 'filterGroups', 'filters', 'filterBrands', 'crumbs', 'meta', 'jsonLd'));
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'brand' => array_values(array_filter((array) $request->query('brand', []))),
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
            'availability' => $request->query('availability'),
            'sort' => in_array($request->query('sort'), ['relevance', 'price_asc', 'price_desc', 'newest', 'name'], true)
                ? $request->query('sort')
                : 'relevance',
            'attr' => array_filter((array) $request->query('attr', []), fn ($value) => is_array($value)),
            'attr_min' => array_filter((array) $request->query('attr_min', []), fn ($value) => is_numeric($value)),
        ];
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['brand'] !== []
            || filled($filters['price_min'])
            || filled($filters['price_max'])
            || filled($filters['availability'])
            || $filters['sort'] !== 'relevance'
            || $filters['attr'] !== []
            || $filters['attr_min'] !== [];
    }
}
