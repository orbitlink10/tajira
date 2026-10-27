<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title),
            'body' => '<p>Test page body.</p>',
            'status' => 'published',
            'published_at' => now(),
            'is_indexable' => true,
            'meta_description' => 'Test page description',
        ];
    }
}
