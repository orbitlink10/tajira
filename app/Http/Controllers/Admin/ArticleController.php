<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use App\Services\RevisionService;
use App\Services\SeoService;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('content.view');
        $articles = Article::query()
            ->with('category:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->query('q').'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        $this->authorize('content.manage');

        return view('admin.articles.form', $this->formData(new Article(['status' => 'draft', 'type' => 'guide', 'is_indexable' => true])));
    }

    public function store(Request $request, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer, RevisionService $revisions)
    {
        $this->authorize('content.manage');
        $data = $this->validated($request, null, $slugs, $sanitizer);
        $productIds = (array) $request->input('products', []);
        unset($data['products']);
        $article = Article::query()->create($data);

        if ($request->hasFile('hero_image')) {
            $result = app(ImageService::class)->convertToWebp(
                $request->file('hero_image')->getRealPath(),
                'articles',
                Str::slug($article->title).'-'.Str::lower(Str::random(6))
            );
            $article->update(['hero_image_path' => $result['path']]);
        }

        $this->syncProducts($article, $productIds);
        $revisions->record($article, $request->user(), 'Created');
        $audit->log('article.created', $article, [], $article->toArray());

        return redirect()->route('admin.articles.index')->with('success', 'Article created.');
    }

    public function edit(Article $article)
    {
        $this->authorize('content.manage');
        $article->load('products');

        return view('admin.articles.form', $this->formData($article));
    }

    public function update(Request $request, Article $article, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer, RevisionService $revisions)
    {
        $this->authorize('content.manage');
        $old = $article->toArray();
        $oldSlug = $article->slug;
        $data = $this->validated($request, $article, $slugs, $sanitizer);
        $productIds = (array) $request->input('products', []);
        unset($data['products']);
        $article->update($data);

        if ($oldSlug !== $article->slug) {
            $slugs->handleSlugChange($article, $oldSlug, '/guides/'.$article->slug, '/guides/'.$oldSlug);
        }

        if ($request->hasFile('hero_image')) {
            $result = app(ImageService::class)->convertToWebp(
                $request->file('hero_image')->getRealPath(),
                'articles',
                Str::slug($article->title).'-'.Str::lower(Str::random(6))
            );
            $article->update(['hero_image_path' => $result['path']]);
        }

        $this->syncProducts($article, $productIds);
        $revisions->record($article, $request->user(), 'Updated');
        $audit->log('article.updated', $article, $old, $article->fresh()->toArray());

        return redirect()->route('admin.articles.index')->with('success', 'Article updated.');
    }

    public function destroy(Article $article, AuditLogger $audit)
    {
        $this->authorize('content.manage');
        $audit->log('article.deleted', $article, $article->toArray());
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Article deleted.');
    }

    public function preview(Article $article, SeoService $seo)
    {
        $this->authorize('content.view');
        $meta = $seo->forArticle($article);
        $meta['robots'] = 'noindex,nofollow';
        $article->load('category', 'products');
        $related = Article::query()->published()->whereKeyNot($article->id)->limit(3)->get();
        $crumbs = [['name' => 'Home', 'url' => '/'], ['name' => 'Guides', 'url' => route('guides.index')], ['name' => $article->title, 'url' => '#']];
        $jsonLd = [];
        $preview = true;

        return view('storefront.guides.show', compact('article', 'related', 'crumbs', 'meta', 'jsonLd', 'preview'));
    }

    private function formData(Article $article): array
    {
        return [
            'article' => $article,
            'categories' => ArticleCategory::query()->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->limit(500)->get(['id', 'name', 'sku']),
            'selectedProducts' => $article->exists ? $article->products->pluck('id')->all() : [],
        ];
    }

    private function validated(Request $request, ?Article $article, SlugService $slugs, HtmlSanitizer $sanitizer): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240'],
            'article_category_id' => ['nullable', 'integer', 'exists:article_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:300000'],
            'type' => ['required', 'in:guide,buying,comparison,setup,compatibility,news'],
            'author_name' => ['nullable', 'string', 'max:140'],
            'status' => ['required', 'in:draft,pending_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'reading_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_override' => ['nullable', 'url', 'max:255'],
            'robots' => ['nullable', 'string', 'max:60'],
            'is_indexable' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'hero_image' => ['nullable', 'image', 'max:8192', 'mimes:jpg,jpeg,png,webp'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:products,id'],
        ]);

        $data['slug'] = $slugs->unique($data['slug'] ?: $data['title'], Article::class, $article?->id);
        $data['body'] = $sanitizer->clean($data['body'] ?? null);
        $data['is_indexable'] = $request->boolean('is_indexable');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['reading_minutes'] = $data['reading_minutes'] ?? max(1, (int) ceil(str_word_count(strip_tags((string) ($data['body'] ?? ''))) / 200));
        $data['published_at'] = $data['status'] === 'published'
            ? ($request->input('published_at') ?: $article?->published_at ?: now())
            : ($request->input('published_at') ?: null);
        $data['updated_by'] = $request->user()->id;
        unset($data['hero_image']);

        if (! $article) {
            $data['created_by'] = $request->user()->id;
        }

        return $data;
    }

    private function syncProducts(Article $article, array $ids): void
    {
        $payload = [];

        foreach (array_values(array_unique(array_map('intval', $ids))) as $index => $id) {
            $payload[$id] = ['position' => $index];
        }

        $article->products()->sync($payload);
    }
}
