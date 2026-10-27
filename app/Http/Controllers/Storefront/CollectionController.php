<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\SeoLandingPage;
use App\Services\SearchService;
use App\Services\SeoService;
use App\Services\StructuredDataService;

class CollectionController extends Controller
{
    public function __invoke(SeoLandingPage $collection, SearchService $search, SeoService $seo, StructuredDataService $structured)
    {
        abort_unless($collection->is_active, 404);

        $categoryIds = $collection->category_id ? [$collection->category_id] : [];
        $filters = array_merge([
            'q' => '',
            'brand' => [],
            'price_min' => null,
            'price_max' => null,
            'availability' => null,
            'sort' => 'relevance',
            'attr' => [],
            'attr_min' => [],
        ], (array) ($collection->filters ?? []));

        $products = $search->results($filters, $categoryIds, $collection->brand_id, (int) config('tajira.seo.pagination_per_page', 24));

        if ($products->total() === 0) {
            abort(404);
        }

        $filterGroups = $search->filterGroups($categoryIds, $collection->brand_id, $filters);
        $filterBrands = Brand::query()->where('is_active', true)->whereHas('publishedProducts')->withCount('publishedProducts')->orderBy('name')->get();

        $crumbs = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => $collection->title, 'url' => route('collections.show', $collection->slug)],
        ];

        $page = $products->currentPage();
        $meta = $seo->build(
            $collection->meta_title ?: $collection->title.' | '.setting('business.name', 'Tajira'),
            $collection->meta_description ?: strip_tags((string) $collection->intro),
            route('collections.show', $collection->slug).($page > 1 ? '?page='.$page : ''),
            $collection->is_indexable ? 'index,follow' : 'noindex,follow'
        );

        $jsonLd = [$structured->breadcrumbs($crumbs)];

        return view('storefront.collections.show', compact('collection', 'products', 'filters', 'filterGroups', 'filterBrands', 'meta', 'crumbs', 'jsonLd'));
    }
}
