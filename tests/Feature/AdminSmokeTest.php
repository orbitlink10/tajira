<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ImportBatch;
use App\Models\NavigationItem;
use App\Models\Order;
use App\Models\Page;
use App\Models\ProductReview;
use App\Models\Quotation;
use App\Models\SeoLandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_administrator_screens_render(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $page = Page::factory()->create();
        $article = Article::factory()->create();
        $review = ProductReview::factory()->create(['product_id' => $product->id]);
        $navigation = NavigationItem::query()->create(['label' => 'Test link', 'url' => '/', 'type' => 'link', 'location' => 'header', 'is_active' => true]);
        $batch = ImportBatch::query()->create(['reference' => 'TEST-BATCH', 'type' => 'discovery', 'status' => 'paused', 'total_rows' => 0]);
        $collection = SeoLandingPage::query()->create([
            'type' => 'category',
            'title' => 'Test Collection',
            'slug' => 'test-collection',
            'intro' => 'A useful collection introduction with enough genuine content for testing.',
            'category_id' => $product->primary_category_id,
            'is_active' => true,
            'is_indexable' => true,
        ]);
        $quotation = Quotation::query()->create(['number' => 'QT-TEST-1', 'name' => 'Buyer', 'email' => 'buyer@example.test', 'phone' => '0712345678', 'status' => 'new']);
        $order = Order::query()->create([
            'number' => 'TJ-TEST-1',
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'currency' => 'KES',
            'subtotal_minor' => 100000,
            'total_minor' => 100000,
            'customer_name' => 'Test Buyer',
            'customer_email' => 'buyer@example.test',
            'customer_phone' => '0712345678',
            'county' => 'Nairobi City',
            'town' => 'Nairobi',
            'address_line' => 'Test address',
            'delivery_type' => 'pickup',
            'placed_at' => now(),
        ]);

        $routes = [
            '/admin',
            '/admin/products',
            '/admin/products/create',
            '/admin/products/'.$product->slug.'/edit',
            '/admin/products/'.$product->slug.'/variants',
            '/admin/revisions?type=product&id='.$product->id,
            '/admin/revisions?type=page&id='.$page->id,
            '/admin/revisions?type=article&id='.$article->id,
            '/admin/products/'.$product->slug.'/variants/create',
            '/admin/categories',
            '/admin/categories/create',
            '/admin/brands',
            '/admin/brands/create',
            '/admin/attributes',
            '/admin/attributes/create',
            '/admin/orders',
            '/admin/orders/'.$order->number,
            '/admin/quotations',
            '/admin/quotations/'.$quotation->number,
            '/admin/contact-messages',
            '/admin/reviews',
            '/admin/pages',
            '/admin/pages/create',
            '/admin/pages/'.$page->slug.'/edit',
            '/admin/articles',
            '/admin/articles/create',
            '/admin/articles/'.$article->slug.'/edit',
            '/admin/article-categories',
            '/admin/article-categories/create',
            '/admin/navigation',
            '/admin/navigation/'.$navigation->id.'/edit',
            '/admin/homepage',
            '/admin/coupons',
            '/admin/coupons/create',
            '/admin/shipping',
            '/admin/redirects',
            '/admin/redirects/create',
            '/admin/seo',
            '/admin/seo/landing-pages',
            '/admin/seo/landing-pages/create',
            '/admin/seo/landing-pages/'.$collection->slug.'/edit',
            '/collections/'.$collection->slug,
            '/admin/imports',
            '/admin/imports/'.$batch->id,
            '/admin/settings',
            '/admin/users',
            '/admin/users/create',
            '/admin/media',
            '/admin/audit',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get($route);

            if ($response->status() !== 200) {
                $this->fail($route.' returned HTTP '.$response->status());
            }
        }

        $this->actingAs($admin)->get('/admin/mfa/setup')->assertOk();
    }
}
