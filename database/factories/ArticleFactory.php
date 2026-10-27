<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(4, true);

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title),
            'excerpt' => 'Test article excerpt.',
            'body' => '<p>Test article body with useful context.</p>',
            'type' => 'guide',
            'status' => 'published',
            'published_at' => now(),
            'is_indexable' => true,
            'meta_description' => 'Test article description',
        ];
    }
}
