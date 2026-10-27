<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\SourceProduct;
use App\Services\Imports\CatalogueImportService;
use App\Services\Imports\ProductPageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_product_page_parser_extracts_structured_source_observations(): void
    {
        $html = <<<'HTML'
<!doctype html><html><head>
<meta property="og:image" content="https://example.com/image.jpg">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Product","name":"Example Switch 8","sku":"EX-SW8","brand":{"@type":"Brand","name":"Example"},"offers":{"@type":"Offer","price":"12000","priceCurrency":"KES"},"additionalProperty":[{"@type":"PropertyValue","name":"Ports","value":"8"}]}
</script></head><body class="postid-123">
<h1 class="product_title">Example Switch 8</h1>
<p class="price"><span class="woocommerce-Price-amount">KSh 12,000</span></p>
<p class="posted_in">Category: <a>Network Switches</a></p>
<table class="woocommerce-product-attributes"><tr><th>Model</th><td>EX-SW8</td></tr><tr><th>PoE budget</th><td>65 W</td></tr></table>
<div class="woocommerce-product-details__short-description"><p>Source short description.</p></div>
<div id="tab-description"><p>Source description that must not be copied automatically.</p></div>
</body></html>
HTML;

        $parsed = app(ProductPageParser::class)->parse($html, 'https://example.com/product/example-switch-8');

        $this->assertSame('Example Switch 8', $parsed['raw_name']);
        $this->assertSame('Example', $parsed['raw_brand']);
        $this->assertSame('EX-SW8', $parsed['raw_sku'] ?? $parsed['raw_model']);
        $this->assertSame(1200000, $parsed['raw_price_minor']);
        $this->assertSame('123', $parsed['external_id']);
        $this->assertContains('Network Switches', $parsed['raw_categories']);
        $this->assertArrayHasKey('PoE budget', $parsed['raw_attributes']);
        $this->assertStringContainsString('must not be copied', $parsed['raw_description']);
        $this->assertNotEmpty($parsed['raw_image_urls']);
    }

    public function test_approval_creates_an_empty_draft_without_competitor_content_or_price(): void
    {
        $brand = Brand::factory()->create(['name' => 'Example']);
        $category = Category::query()->create(['name' => 'Network Switches', 'slug' => 'network-switches-import', 'is_active' => true]);
        $admin = $this->admin();

        $source = SourceProduct::query()->create([
            'source_url' => 'https://example.com/product/example-switch-8',
            'raw_name' => 'Example Switch 8',
            'raw_brand' => 'Example',
            'raw_model' => 'EX-SW8',
            'raw_mpn' => 'EX-SW8',
            'raw_price_minor' => 1200000,
            'raw_description' => 'Competitor copy that must not be published.',
            'raw_categories' => ['Network Switches'],
            'status' => 'needs_review',
            'discovered_at' => now(),
            'proposed_sku' => 'EXAMPLE-EX-SW8',
        ]);

        $product = app(CatalogueImportService::class)->approve($source, $admin);

        $this->assertSame(Product::STATUS_DRAFT, $product->status);
        $this->assertNull($product->price_minor);
        $this->assertNull($product->description);
        $this->assertNull($product->short_description);
        $this->assertSame($brand->id, $product->brand_id);
        $this->assertSame($category->id, $product->primary_category_id);
        $this->assertSame($product->id, $source->fresh()->product_id);
        $this->assertNull($source->fresh()->verified_at);
    }

    public function test_csv_dry_run_validates_without_creating_products_then_creates_drafts(): void
    {
        $admin = $this->admin();
        Brand::factory()->create(['name' => 'Acme Import']);

        $csv = "name,sku,brand,price_kes\nCSV Test Product,CSV-TEST-1,Acme Import,1200\n";

        $this->actingAs($admin)->post(route('admin.products.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            'dry_run' => '1',
        ])->assertRedirect();

        $this->assertDatabaseCount('products', 0);
        $this->assertSame(1, ImportBatch::query()->count());

        $this->actingAs($admin)->post(route('admin.products.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['sku' => 'CSV-TEST-1', 'status' => Product::STATUS_DRAFT, 'price_minor' => 120000]);
    }
}
