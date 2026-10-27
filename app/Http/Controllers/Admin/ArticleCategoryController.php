<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Services\AuditLogger;
use App\Services\SlugService;
use Illuminate\Http\Request;

class ArticleCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('content.view');
        $categories = ArticleCategory::query()->withCount('articles')->orderBy('position')->get();

        return view('admin.article-categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('content.manage');

        return view('admin.article-categories.form', ['category' => new ArticleCategory]);
    }

    public function store(Request $request, SlugService $slugs, AuditLogger $audit)
    {
        $this->authorize('content.manage');
        $data = $this->validated($request, null, $slugs);
        $category = ArticleCategory::query()->create($data);
        $audit->log('article-category.created', $category, [], $category->toArray());

        return redirect()->route('admin.article-categories.index')->with('success', 'Article category created.');
    }

    public function edit(ArticleCategory $articleCategory)
    {
        $this->authorize('content.manage');

        return view('admin.article-categories.form', ['category' => $articleCategory]);
    }

    public function update(Request $request, ArticleCategory $articleCategory, SlugService $slugs, AuditLogger $audit)
    {
        $this->authorize('content.manage');
        $old = $articleCategory->toArray();
        $articleCategory->update($this->validated($request, $articleCategory, $slugs));
        $audit->log('article-category.updated', $articleCategory, $old, $articleCategory->fresh()->toArray());

        return redirect()->route('admin.article-categories.index')->with('success', 'Article category updated.');
    }

    public function destroy(ArticleCategory $articleCategory, AuditLogger $audit)
    {
        $this->authorize('content.manage');

        if ($articleCategory->articles()->exists()) {
            return back()->with('error', 'Move articles to another category first.');
        }

        $audit->log('article-category.deleted', $articleCategory);
        $articleCategory->delete();

        return redirect()->route('admin.article-categories.index')->with('success', 'Article category deleted.');
    }

    private function validated(Request $request, ?ArticleCategory $category, SlugService $slugs): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:3000'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = $slugs->unique($data['slug'] ?: $data['name'], ArticleCategory::class, $category?->id);
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }
}
