<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(SeoService $seo, StructuredDataService $structured)
    {
        $featured = Cache::remember('tajira.home.featured', 1800, function () {
            $products = Product::query()
                ->published()
                ->featured()
                ->with(['brand:id,name,slug', 'primaryImage', 'activeVariants'])
                ->orderByDesc('published_at')
                ->limit(8)
                ->get();

            if ($products->isEmpty()) {
                $products = Product::query()
                    ->published()
                    ->with(['brand:id,name,slug', 'primaryImage', 'activeVariants'])
                    ->orderByDesc('published_at')
                    ->limit(8)
                    ->get();
            }

            return $products;
        });

        $categories = Cache::remember('tajira.home.categories', 3600, function () {
            return Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('position')
                ->limit(8)
                ->get()
                ->each(function (Category $category) {
                    $category->setAttribute('published_products_count', $category->publishedProductCountDeep());
                });
        });

        $brands = Cache::remember('tajira.brands.active', 3600, function () {
            return Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug', 'logo_path']);
        });

        $guides = Article::query()
            ->published()
            ->where('is_indexable', true)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $sections = Cache::remember('tajira.home.sections', 3600, function () {
            return HomepageSection::query()->where('is_active', true)->orderBy('position')->get()->keyBy('key');
        });

        $meta = $seo->build(
            setting('seo.default_title', 'Tajira | Networking equipment in Kenya'),
            setting('seo.default_description'),
            url('/')
        );

        $jsonLd = array_values(array_filter([
            $structured->organization(),
            $structured->website(),
            $structured->localBusiness(),
        ]));

        return view('storefront.home', compact('featured', 'categories', 'brands', 'guides', 'sections', 'meta', 'jsonLd'));
    }
}
