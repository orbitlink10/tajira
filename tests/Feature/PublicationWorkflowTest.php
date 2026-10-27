<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class PublicationWorkflowTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_publish_requires_commercial_and_content_data(): void
    {
        $admin = $this->admin();
        $product = $this->product([
            'status' => Product::STATUS_DRAFT,
            'published_at' => null,
            'price_minor' => null,
            'price_on_request' => false,
            'short_description' => null,
            'description' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.publish', $product))
            ->assertSessionHas('error');

        $this->assertSame(Product::STATUS_DRAFT, $product->fresh()->status);

        $product->update([
            'price_minor' => 150000,
            'short_description' => 'A verified test product summary.',
            'description' => '<p>Description</p>',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.publish', $product))
            ->assertSessionHas('success');

        $this->assertSame(Product::STATUS_PUBLISHED, $product->fresh()->status);
        $this->assertNotNull($product->fresh()->published_at);

        $this->get(route('products.show', $product))->assertOk()->assertSee($product->name);
    }

    public function test_unpublishing_removes_the_public_product_page(): void
    {
        $admin = $this->admin();
        $product = $this->product();

        $this->actingAs($admin)->post(route('admin.products.unpublish', $product))->assertSessionHas('success');

        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_slug_change_creates_a_redirect_and_old_path_resolves(): void
    {
        $admin = $this->admin();
        $page = Page::factory()->create(['slug' => 'old-policy', 'title' => 'Old policy']);

        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => 'Old policy',
            'slug' => 'new-policy',
            'template' => 'default',
            'status' => 'published',
            'body' => '<p>Updated policy.</p>',
            'is_indexable' => '1',
            'show_in_footer' => '1',
        ])->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseHas('redirects', ['from_path' => '/pages/old-policy', 'to_path' => '/pages/new-policy']);

        $this->get('/pages/old-policy')->assertRedirect('/pages/new-policy');
    }

    public function test_redirect_fallback_returns_404_when_no_redirect_exists(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound();
    }
}
