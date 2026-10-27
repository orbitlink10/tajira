<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Str;

class SeoService
{
    public function build(
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        string $robots = 'index,follow',
        ?string $image = null,
        string $type = 'website',
        ?string $previous = null,
        ?string $next = null
    ): array {
        $business = setting('business.name', 'Tajira');
        $title = trim((string) ($title ?: setting('seo.default_title', $business.' | Networking equipment in Kenya')));

        if ($title !== '' && ! str_contains(Str::lower($title), Str::lower($business)) && mb_strlen($title) < 45) {
            $title .= ' | '.$business;
        }

        $description = trim(strip_tags((string) ($description ?: setting('seo.default_description', ''))));
        $description = Str::limit(preg_replace('/\s+/', ' ', $description) ?? $description, 158);

        if ($this->globalNoIndex()) {
            $robots = 'noindex,nofollow';
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical ? url($canonical) : url()->current(),
            'robots' => $robots,
            'image' => $image ? url($image) : asset('images/og-default.png'),
            'type' => $type,
            'previous' => $previous,
            'next' => $next,
            'gsc_verification' => setting('seo.gsc_verification'),
        ];
    }

    public function forProduct(Product $product): array
    {
        $image = $product->primaryImage?->path ? asset('storage/'.$product->primaryImage->path) : null;
        $robots = $product->robots ?: ($product->isIndexable() ? 'index,follow' : 'noindex,follow');
        $canonical = str_contains($robots, 'noindex') ? null : $product->canonicalUrl();

        return $this->build(
            $product->seoTitle(),
            $product->seoDescription(),
            $canonical,
            $robots,
            $image,
            'product'
        );
    }

    public function forCategory(Category $category, int $page = 1): array
    {
        $canonical = $category->canonical_override ?: route('categories.show', $category->slug).($page > 1 ? '?page='.$page : '');

        return $this->build(
            $category->seoTitle().($page > 1 ? ' — Page '.$page : ''),
            $category->seoDescription(),
            $canonical,
            $category->is_indexable ? 'index,follow' : 'noindex,follow',
            $category->image_path ? asset('storage/'.$category->image_path) : null,
            'website'
        );
    }

    public function forBrand(Brand $brand, int $page = 1): array
    {
        $canonical = $brand->canonical_override ?: route('brands.show', $brand->slug).($page > 1 ? '?page='.$page : '');

        return $this->build(
            $brand->seoTitle().($page > 1 ? ' — Page '.$page : ''),
            $brand->seoDescription(),
            $canonical,
            $brand->is_indexable ? 'index,follow' : 'noindex,follow',
            $brand->logo_path ? asset('storage/'.$brand->logo_path) : null
        );
    }

    public function forArticle(Article $article): array
    {
        return $this->build(
            $article->seoTitle(),
            $article->meta_description ?: $article->excerpt,
            $article->is_indexable ? $article->canonical_override ?: route('guides.show', $article->slug) : null,
            $article->robots ?: ($article->is_indexable ? 'index,follow' : 'noindex,follow'),
            $article->hero_image_path ? asset('storage/'.$article->hero_image_path) : null,
            'article'
        );
    }

    public function forPage(Page $page): array
    {
        return $this->build(
            $page->seoTitle(),
            $page->meta_description ?: $page->excerpt,
            $page->is_indexable ? $page->canonical_override ?: route('pages.show', $page->slug) : null,
            $page->robots ?: ($page->is_indexable ? 'index,follow' : 'noindex,follow')
        );
    }

    public function globalNoIndex(): bool
    {
        return (bool) config('tajira.seo.global_noindex', false)
            || app()->environment('staging');
    }
}
