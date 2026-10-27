<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('catalogue.view');
        $categories = Category::query()
            ->with('parent')
            ->withCount(['products', 'publishedProducts'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('catalogue.manage');
        $categories = $this->options();

        return view('admin.categories.form', ['category' => new Category(['is_active' => true, 'is_indexable' => true, 'show_in_menu' => true]), 'categories' => $categories]);
    }

    public function store(Request $request, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer)
    {
        $this->authorize('catalogue.manage');
        $data = $this->validated($request, null, $slugs, $sanitizer);
        $category = Category::query()->create($data);
        $audit->log('category.created', $category, [], $category->toArray());

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        $this->authorize('catalogue.manage');
        $categories = $this->options($category->id, $category->descendantIds());

        return view('admin.categories.form', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer)
    {
        $this->authorize('catalogue.manage');
        $old = $category->toArray();
        $oldSlug = $category->slug;
        $data = $this->validated($request, $category, $slugs, $sanitizer);
        $category->update($data);

        if ($oldSlug !== $category->slug) {
            $slugs->handleSlugChange($category, $oldSlug, '/categories/'.$category->slug, '/categories/'.$oldSlug);
        }

        $audit->log('category.updated', $category, $old, $category->fresh()->toArray());

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');

        if ($category->children()->exists()) {
            return back()->with('error', 'Move or delete child categories first.');
        }

        if ($category->products()->exists()) {
            return back()->with('error', 'This category still has products. Reassign them before deleting the category.');
        }

        $audit->log('category.deleted', $category, $category->toArray());
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?Category $category, SlugService $slugs, HtmlSanitizer $sanitizer): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'slug' => ['nullable', 'string', 'max:180'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn($category ? $category->descendantIds() : [])],
            'short_name' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'buying_advice' => ['nullable', 'string', 'max:20000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_override' => ['nullable', 'url', 'max:255'],
            'is_indexable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_menu' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['description'] = isset($data['description']) ? strip_tags($data['description']) : null;
        $data['buying_advice'] = $sanitizer->clean($data['buying_advice'] ?? null);
        $data['slug'] = $slugs->unique($data['slug'] ?: $data['name'], Category::class, $category?->id);
        $data['is_indexable'] = $request->boolean('is_indexable');
        $data['is_active'] = $request->boolean('is_active');
        $data['show_in_menu'] = $request->boolean('show_in_menu');
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }

    private function options(?int $excludeId = null, array $excludedIds = []): array
    {
        return Category::query()
            ->whereNotIn('id', array_merge($excludedIds, $excludeId ? [$excludeId] : []))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Category $category) => [$category->id => $category->name])
            ->all();
    }
}
