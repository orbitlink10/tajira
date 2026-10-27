<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_homepage_renders_without_published_products(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Networking equipment for the way Kenya connects.')
            ->assertSee('The catalogue is being prepared.');
    }

    public function test_footer_only_shows_pages_marked_for_the_footer(): void
    {
        Page::factory()->create(['title' => 'Visible Policy', 'slug' => 'visible-policy', 'show_in_footer' => true]);
        Page::factory()->create(['title' => 'Hidden Policy', 'slug' => 'hidden-policy', 'show_in_footer' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Visible Policy')
            ->assertDontSee('Hidden Policy');
    }
}
