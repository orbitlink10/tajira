<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Support\Facades\Storage;

class LinkAuditService
{
    private const KNOWN_PREFIXES = [
        '/', '/products', '/categories', '/brands', '/guides', '/pages', '/contact', '/search',
        '/cart', '/checkout', '/quote', '/compare', '/track', '/login', '/register', '/account',
        '/sitemap.xml', '/robots.txt',
    ];

    public function run(): array
    {
        $links = [];

        foreach (Product::query()->whereNotNull('description')->cursor() as $product) {
            $this->collect($product->description, $links);
        }

        foreach (Page::query()->whereNotNull('body')->cursor() as $page) {
            $this->collect($page->body, $links);
        }

        foreach (Article::query()->whereNotNull('body')->cursor() as $article) {
            $this->collect($article->body, $links);
        }

        $issues = [];
        $checked = 0;

        foreach (array_slice(array_keys($links), 0, 500) as $path) {
            $checked++;
            $reason = $this->resolve($path);

            if ($reason !== null) {
                $issues[] = ['url' => $path, 'reason' => $reason, 'sources' => array_values(array_slice($links[$path], 0, 5))];
            }
        }

        $report = [
            'checked_at' => now()->toIso8601String(),
            'checked' => $checked,
            'issues' => $issues,
        ];

        Storage::disk('local')->put('seo/link-audit.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $report;
    }

    private function collect(?string $html, array &$links): void
    {
        if (! $html || ! preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return;
        }

        foreach ($matches[1] as $href) {
            $href = trim($href);

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            $parts = parse_url($href);

            if (isset($parts['host']) && ! in_array(strtolower($parts['host']), [strtolower((string) parse_url(config('app.url'), PHP_URL_HOST)), 'localhost', '127.0.0.1'], true)) {
                continue;
            }

            $path = $parts['path'] ?? '/';
            $path = '/'.ltrim($path, '/');
            $path = $path !== '/' ? rtrim($path, '/') : '/';

            $links[$path] ??= [];
            $links[$path][] = 'content link';
            $links[$path] = array_values(array_unique($links[$path]));
        }
    }

    private function resolve(string $path): ?string
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if ($segments === []) {
            return null;
        }

        if (count($segments) >= 2) {
            [$section, $slug] = $segments;

            $exists = match ($section) {
                'products' => Product::query()->published()->where('slug', $slug)->exists(),
                'categories' => Category::query()->where('slug', $slug)->where('is_active', true)->exists(),
                'brands' => $slug ? Brand::query()->where('slug', $slug)->where('is_active', true)->exists() : true,
                'guides' => Article::query()->published()->where('slug', $slug)->exists(),
                'pages' => Page::query()->published()->where('slug', $slug)->exists(),
                default => null,
            };

            if ($exists === true) {
                return null;
            }

            if (Redirect::query()->where('from_path', $path)->where('is_active', true)->exists()) {
                return null;
            }

            if ($exists === false) {
                return 'Target content is missing, unpublished or inactive.';
            }
        }

        foreach (self::KNOWN_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/').'/')) {
                return null;
            }
        }

        if (Redirect::query()->where('from_path', $path)->where('is_active', true)->exists()) {
            return null;
        }

        return 'No matching route, content or active redirect.';
    }
}
