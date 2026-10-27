<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SeoLandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_product_page_has_server_rendered_content_canonical_and_structured_data(): void
    {
        $product = $this->product(['name' => 'Test Router 3000', 'price_minor' => 250000]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('<h1', false);
        $response->assertSee('Test Router 3000');
        $response->assertSee('<link rel="canonical" href="'.route('products.show', $product).'"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"@type":"Offer"', false);
        $response->assertSee('KES 2,500');
    }

    public function test_quote_only_products_do_not_publish_offer_structured_data(): void
    {
        $product = $this->product(['price_minor' => null, 'price_on_request' => true]);

        $response = $this->get(route('products.show', $product));
        $response->assertOk();
        $response->assertSee('Request price');
        $response->assertDontSee('"@type":"Offer"', false);
    }

    public function test_product_sitemap_excludes_drafts_and_non_indexable_products(): void
    {
        $published = $this->product();
        $draft = $this->product(['status' => Product::STATUS_DRAFT, 'published_at' => null]);
        $hidden = $this->product(['is_indexable' => false]);

        $response = $this->get(route('sitemaps.products'));

        $response->assertOk();
        $response->assertSee(route('products.show', $published), false);
        $response->assertDontSee(route('products.show', $draft), false);
        $response->assertDontSee(route('products.show', $hidden), false);
    }

    public function test_search_pages_are_noindex_and_robots_blocks_admin(): void
    {
        $this->get('/search?q=router')->assertOk()->assertSee('<meta name="robots" content="noindex,follow">', false);

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin');
        $this->get('/robots.txt')->assertDontSee('Disallow: /'."\n");
    }

    public function test_paginated_category_pages_are_self_canonical(): void
    {
        config()->set('tajira.seo.pagination_per_page', 2);
        $category = Category::query()->create([
            'name' => 'Pagination Test',
            'slug' => 'pagination-test',
            'is_active' => true,
            'is_indexable' => true,
        ]);

        foreach (range(1, 3) as $index) {
            $product = $this->product(['name' => 'Page Product '.$index]);
            $product->categories()->attach($category->id, ['is_primary' => true, 'position' => 0]);
        }

        $response = $this->get(route('categories.show', $category).'?page=2');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('categories.show', $category).'?page=2">', false);
    }

    public function test_approved_collections_are_indexable_and_included_in_sitemaps(): void
    {
        $product = $this->product();
        $collection = SeoLandingPage::query()->create([
            'type' => 'category',
            'title' => 'Approved Switch Collection',
            'slug' => 'approved-switch-collection',
            'intro' => 'A genuine collection introduction for approved landing page tests.',
            'category_id' => $product->primary_category_id,
            'is_active' => true,
            'is_indexable' => true,
        ]);

        $response = $this->get(route('collections.show', $collection));
        $response->assertOk();
        $response->assertSee('Approved Switch Collection');
        $response->assertSee($product->name);
        $response->assertSee('<meta name="robots" content="index,follow">', false);

        $sitemap = $this->get(route('sitemaps.collections'));
        $sitemap->assertOk()->assertSee(route('collections.show', $collection), false);
    }

    public function test_private_pages_are_marked_noindex_and_are_not_publicly_cached(): void
    {
        $response = $this->get('/cart');

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-cache', strtolower((string) $response->headers->get('Cache-Control')));
    }
}
