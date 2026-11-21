<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class AdminCatalogueTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_admin_can_create_and_update_a_product_with_structured_attributes_and_slug_redirect(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $brand = Brand::factory()->create(['name' => 'Admin Brand']);
        $category = Category::factory()->create(['name' => 'Admin Switches']);
        $attribute = Attribute::query()->create([
            'name' => 'Port Count Test',
            'slug' => 'port-count-test',
            'code' => 'PORT_COUNT_TEST',
            'type' => 'select',
            'is_filterable' => true,
            'is_comparable' => true,
        ]);
        $value = $attribute->values()->create(['value' => '8 ports', 'slug' => '8-ports', 'numeric_value' => 8]);

        $payload = [
            'product_image' => UploadedFile::fake()->image('switch.jpg', 100, 100),
            'name' => 'Admin Created Switch 8',
            'slug' => 'admin-created-switch-8',
            'sku' => 'ADMIN-SW-8',
            'brand_id' => $brand->id,
            'primary_category_id' => $category->id,
            'model' => 'ADMIN-SW-8',
            'product_type' => 'simple',
            'status' => 'draft',
            'tax_mode' => 'default',
            'price' => '1200',
            'stock_quantity' => '3',
            'low_stock_threshold' => '1',
            'short_description' => 'An admin-created switch used for a feature test.',
            'description' => '<p>Original test description.</p>',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'key_features_text' => "Feature one\nFeature two",
            'categories' => [$category->id],
            'attributes' => [
                $attribute->id => ['value_id' => $value->id],
            ],
            'is_indexable' => '1',
        ];

        $invalid = $payload;
        $invalid['product_image'] = UploadedFile::fake()->create('not-an-image.pdf', 20, 'application/pdf');
        $this->actingAs($admin)->post(route('admin.products.store'), $invalid)->assertSessionHasErrors('product_image');
        $this->assertDatabaseMissing('products', ['sku' => 'ADMIN-SW-8']);
        $this->actingAs($admin)->post(route('admin.products.store'), $payload)->assertRedirect();

        $product = Product::query()->where('sku', 'ADMIN-SW-8')->firstOrFail();
        $this->assertSame(Product::STATUS_DRAFT, $product->status);
        $image = $product->images()->firstOrFail();
        $this->assertTrue($image->is_primary);
        $this->assertSame($product->name, $image->alt);
        $this->assertSame('image/webp', $image->mime);
        Storage::disk('public')->assertExists($image->path);
        $this->assertSame(120000, $product->price_minor);
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $product->youtube_url);
        $this->assertSame($category->id, $product->primary_category_id);
        $this->assertDatabaseHas('product_categories', ['product_id' => $product->id, 'category_id' => $category->id, 'is_primary' => 1]);
        $this->assertDatabaseHas('product_attribute_values', ['product_id' => $product->id, 'attribute_id' => $attribute->id, 'attribute_value_id' => $value->id]);

        $update = $payload;
        unset($update['product_image']);
        $update['slug'] = 'admin-created-switch-8-renamed';
        $update['status'] = 'published';
        $update['price'] = '1500';
        unset($update['attributes'][$attribute->id]['value_id']);
        $update['attributes'][$attribute->id]['value_id'] = $value->id;

        $this->actingAs($admin)->put(route('admin.products.update', $product), $update)->assertRedirect(route('admin.products.edit', $product->fresh()));

        $product->refresh();
        $this->assertSame(Product::STATUS_PUBLISHED, $product->status);
        $this->assertSame(150000, $product->price_minor);
        $pricingUpdate = $update;
        $pricingUpdate['sale_price'] = '1200';
        $pricingUpdate['price_on_request'] = '1';
        $this->put(route('admin.products.update', $product), $pricingUpdate)->assertSessionHasNoErrors();
        $this->assertFalse($product->fresh()->price_on_request);
        $this->assertSame(120000, $product->fresh()->effectivePriceMinor());

        $pricingUpdate['price_visibility'] = 'quote';
        $this->put(route('admin.products.update', $product), $pricingUpdate)->assertSessionHasNoErrors();
        $this->assertTrue($product->fresh()->price_on_request);

        $pricingUpdate['price_visibility'] = 'public';
        $this->put(route('admin.products.update', $product), $pricingUpdate)->assertSessionHasNoErrors();
        $this->assertFalse($product->fresh()->price_on_request);
        $this->assertSame(1, $product->images()->count());
        $this->assertSame('admin-created-switch-8-renamed', $product->slug);
        $this->assertDatabaseHas('redirects', ['from_path' => '/products/admin-created-switch-8', 'to_path' => '/products/admin-created-switch-8-renamed']);

        $this->get(route('products.show', $product))->assertOk()->assertSee('Admin Created Switch 8')->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
        $update['youtube_url'] = '';
        $this->actingAs($admin)->put(route('admin.products.update', $product), $update)->assertRedirect();
        $this->assertNull($product->fresh()->youtube_url);
        $this->get('/products/admin-created-switch-8')->assertRedirect('/products/admin-created-switch-8-renamed');
    }
}
