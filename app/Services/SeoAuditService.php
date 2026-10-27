<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SeoAuditService
{
    public function metrics(): array
    {
        $published = Product::query()->published();

        $duplicateTitles = Product::query()
            ->published()
            ->whereNotNull('meta_title')
            ->select('meta_title')
            ->groupBy('meta_title')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $brokenLinks = $this->brokenLinksReport();

        return [
            'published_products' => (clone $published)->count(),
            'missing_meta_title' => (clone $published)->where(fn ($q) => $q->whereNull('meta_title')->orWhere('meta_title', ''))->count(),
            'missing_meta_description' => (clone $published)->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))->count(),
            'missing_description' => (clone $published)->where(fn ($q) => $q->whereNull('short_description')->orWhere('short_description', ''))->count(),
            'missing_images' => (clone $published)->doesntHave('images')->count(),
            'missing_alt' => ProductImage::query()->where(fn ($q) => $q->whereNull('alt')->orWhere('alt', ''))->count(),
            'missing_primary_category' => (clone $published)->whereNull('primary_category_id')->count(),
            'orphan_products' => (clone $published)->doesntHave('categories')->count(),
            'missing_specifications' => (clone $published)->doesntHave('attributeValues')->count(),
            'missing_price_or_quote' => (clone $published)->whereNull('price_minor')->where('price_on_request', false)->count(),
            'duplicate_titles' => $duplicateTitles,
            'pages_missing_description' => Page::query()->published()->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))->count(),
            'articles_missing_description' => Article::query()->published()->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))->count(),
            'broken_internal_links' => count($brokenLinks['issues'] ?? []),
            'link_audit_at' => $brokenLinks['checked_at'] ?? null,
        ];
    }

    public function problemProducts(int $limit = 20): array
    {
        return [
            'missing_meta' => Product::query()->published()
                ->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', ''))
                ->orderByDesc('updated_at')->limit($limit)->get(),
            'missing_images' => Product::query()->published()->doesntHave('images')
                ->orderByDesc('updated_at')->limit($limit)->get(),
            'orphans' => Product::query()->published()->doesntHave('categories')
                ->orderByDesc('updated_at')->limit($limit)->get(),
            'no_primary_category' => Product::query()->published()->whereNull('primary_category_id')
                ->orderByDesc('updated_at')->limit($limit)->get(),
            'missing_specs' => Product::query()->published()->doesntHave('attributeValues')
                ->orderByDesc('updated_at')->limit($limit)->get(),
        ];
    }

    public function duplicateTitleGroups(int $limit = 20): array
    {
        return DB::table('products')
            ->select('meta_title', DB::raw('COUNT(*) as total'))
            ->whereNotNull('meta_title')
            ->where('status', 'published')
            ->groupBy('meta_title')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'meta_title' => $row->meta_title,
                'total' => $row->total,
                'products' => Product::query()->published()->where('meta_title', $row->meta_title)->limit(10)->get(['name', 'slug', 'sku']),
            ])
            ->all();
    }

    public function brokenLinksReport(): array
    {
        if (! Storage::disk('local')->exists('seo/link-audit.json')) {
            return ['checked_at' => null, 'issues' => []];
        }

        $data = json_decode((string) Storage::disk('local')->get('seo/link-audit.json'), true);

        return is_array($data) ? $data : ['checked_at' => null, 'issues' => []];
    }
}
