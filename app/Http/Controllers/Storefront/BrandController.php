<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Services\SearchService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(SeoService $seo)
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->withCount(['publishedProducts'])
            ->orderBy('name')
            ->get();

        $meta = $seo->build(
            'Networking brands in Kenya | '.setting('business.name', 'Tajira'),
            'Browse networking, fibre, security and telephony brands available from Tajira.',
            route('brands.index')
        );

        return view('storefront.brands.index', compact('brands', 'meta'));
    }

    public function show(Request $request, Brand $brand, SearchService $search, SeoService $seo, StructuredDataService $structured)
    {
        abort_unless($brand->is_active, 404);

        $filters = [
            'q' => '',
            'brand' => [],
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
            'availability' => $request->query('availability'),
            'sort' => in_array($request->query('sort'), ['price_asc', 'price_desc', 'newest', 'name'], true)
                ? $request->query('sort')
                : 'newest',
            'attr' => array_filter((array) $request->query('attr', []), fn ($value) => is_array($value)),
            'attr_min' => array_filter((array) $request->query('attr_min', []), fn ($value) => is_numeric($value)),
        ];

        $products = $search->results($filters, [], $brand->id, (int) config('tajira.seo.pagination_per_page', 24));
        $filterGroups = $search->filterGroups([], $brand->id, $filters);

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->where('brand_id', $brand->id)->published())
            ->withCount(['publishedProducts' => fn ($query) => $query->where('brand_id', $brand->id)])
            ->orderBy('name')
            ->get();

        $crumbs = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'Brands', 'url' => route('brands.index')],
            ['name' => $brand->name, 'url' => route('brands.show', $brand->slug)],
        ];

        $page = $products->currentPage();
        $hasFilters = filled($request->query('price_min')) || filled($request->query('price_max'))
            || filled($request->query('availability')) || $filters['attr'] !== [] || $filters['attr_min'] !== [];
        $meta = $seo->forBrand($brand, $page);

        if ($hasFilters) {
            $meta['robots'] = 'noindex,follow';
            $meta['canonical'] = route('brands.show', $brand->slug).($page > 1 ? '?page='.$page : '');
        }

        $jsonLd = [$structured->breadcrumbs($crumbs)];

        return view('storefront.brands.show', compact('brand', 'products', 'filterGroups', 'filters', 'categories', 'crumbs', 'meta', 'jsonLd'));
    }
}
