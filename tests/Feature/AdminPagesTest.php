<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_bulk_actions_affect_only_selected_pages_and_require_content_permission(): void
    {
        $selected = Page::factory()->create(['status' => 'draft']);
        $other = Page::factory()->create(['status' => 'draft']);
        $this->actingAs($this->admin(User::ROLE_ORDER_MANAGER))->post(route('admin.pages.bulk'), ['ids' => [$selected->id], 'action' => 'published'])->assertForbidden();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.pages.bulk'), ['ids' => [$selected->id], 'action' => 'published'])->assertRedirect(route('admin.pages.index'));
        $this->assertSame('published', $selected->fresh()->status);
        $this->assertSame('draft', $other->fresh()->status);
        $this->assertTrue($selected->fresh()->isPublished());
        $this->actingAs($admin)->post(route('admin.pages.bulk'), ['ids' => [$selected->id], 'action' => 'delete'])->assertRedirect();
        $this->assertSoftDeleted($selected);
        $this->assertNotSoftDeleted($other);
    }

    public function test_page_editor_saves_formatted_content_and_seo(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Our services', 'slug' => 'our-services', 'template' => 'default', 'status' => 'draft',
            'meta_title' => 'Services at Tajira', 'meta_description' => 'Explore our services.',
            'body' => '<h2>Equipment</h2><p><strong>Expert advice</strong></p><script>alert(1)</script>',
        ])->assertRedirect(route('admin.pages.index'));
        $page = Page::where('slug', 'our-services')->firstOrFail();
        $this->assertSame('Services at Tajira', $page->meta_title);
        $this->assertStringContainsString('<strong>Expert advice</strong>', $page->body);
        $this->assertStringNotContainsString('<script', $page->body);
    }
}
