<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoLandingPage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapService
{
    private const TTL = 3600;

    public function index(): string
    {
        return Cache::remember('sitemap.index.v1', self::TTL, function () {
            $children = [
                ['loc' => route('sitemaps.pages'), 'lastmod' => Page::query()->published()->max('updated_at')],
                ['loc' => route('sitemaps.categories'), 'lastmod' => Category::query()->where('is_active', true)->max('updated_at')],
                ['loc' => route('sitemaps.brands'), 'lastmod' => Brand::query()->where('is_active', true)->max('updated_at')],
                ['loc' => route('sitemaps.products'), 'lastmod' => Product::query()->published()->max('updated_at')],
                ['loc' => route('sitemaps.articles'), 'lastmod' => Article::query()->published()->max('updated_at')],
                ['loc' => route('sitemaps.collections'), 'lastmod' => SeoLandingPage::query()->where('is_active', true)->max('updated_at')],
            ];

            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($children as $child) {
                if (! $child['lastmod']) {
                    continue;
                }

                $xml .= '  <sitemap><loc>'.e($child['loc']).'</loc><lastmod>'.e($this->date($child['lastmod'])).'</lastmod></sitemap>'."\n";
            }

            return $xml.'</sitemapindex>';
        });
    }

    public function pages(): string
    {
        return Cache::remember('sitemap.pages.v1', self::TTL, function () {
            $urls = Page::query()
                ->published()
                ->where('is_indexable', true)
                ->where(fn ($q) => $q->whereNull('robots')->orWhere('robots', 'not like', '%noindex%'))
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(fn (Page $page) => [
                    'loc' => route('pages.show', $page->slug),
                    'lastmod' => $page->updated_at,
                ])
                ->all();

            return $this->urlset($urls);
        });
    }

    public function categories(): string
    {
        return Cache::remember('sitemap.categories.v1', self::TTL, function () {
            $urls = Category::query()
                ->where('is_active', true)
                ->where('is_indexable', true)
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(fn (Category $category) => [
                    'loc' => route('categories.show', $category->slug),
                    'lastmod' => $category->updated_at,
                ])
                ->all();

            return $this->urlset($urls);
        });
    }

    public function brands(): string
    {
        return Cache::remember('sitemap.brands.v1', self::TTL, function () {
            $urls = Brand::query()
                ->where('is_active', true)
                ->where('is_indexable', true)
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(fn (Brand $brand) => [
                    'loc' => route('brands.show', $brand->slug),
                    'lastmod' => $brand->updated_at,
                ])
                ->all();

            return $this->urlset($urls);
        });
    }

    public function products(): string
    {
        return Cache::remember('sitemap.products.v1', self::TTL, function () {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

            Product::query()
                ->indexable()
                ->with('primaryImage')
                ->orderByDesc('updated_at')
                ->chunk(500, function ($products) use (&$xml) {
                    foreach ($products as $product) {
                        $xml .= '  <url><loc>'.e(route('products.show', $product->slug)).'</loc><lastmod>'.e($this->date($product->updated_at)).'</lastmod>';

                        if ($product->primaryImage) {
                            $xml .= '<image:image><image:loc>'.e($product->primaryImage->url()).'</image:loc><image:title>'.e($product->name).'</image:title></image:image>';
                        }

                        $xml .= '</url>'."\n";
                    }
                });

            return $xml.'</urlset>';
        });
    }

    public function articles(): string
    {
        return Cache::remember('sitemap.articles.v1', self::TTL, function () {
            $urls = Article::query()
                ->indexable()
                ->where(fn ($q) => $q->whereNull('robots')->orWhere('robots', 'not like', '%noindex%'))
                ->orderByDesc('published_at')
                ->get()
                ->map(fn (Article $article) => [
                    'loc' => route('guides.show', $article->slug),
                    'lastmod' => $article->updated_at,
                ])
                ->all();

            return $this->urlset($urls);
        });
    }

    public function collections(): string
    {
        return Cache::remember('sitemap.collections.v1', self::TTL, function () {
            $urls = SeoLandingPage::query()
                ->where('is_active', true)
                ->where('is_indexable', true)
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn (SeoLandingPage $page) => [
                    'loc' => route('collections.show', $page->slug),
                    'lastmod' => $page->updated_at,
                ])
                ->all();

            return $this->urlset($urls);
        });
    }

    public function flush(): void
    {
        foreach (['index', 'pages', 'categories', 'brands', 'products', 'articles', 'collections'] as $sitemap) {
            Cache::forget('sitemap.'.$sitemap.'.v1');
        }
    }

    private function urlset(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc><lastmod>'.e($this->date($url['lastmod'])).'</lastmod></url>'."\n";
        }

        return $xml.'</urlset>';
    }

    private function date($date): string
    {
        return $date ? Carbon::parse($date)->toAtomString() : now()->toAtomString();
    }
}
