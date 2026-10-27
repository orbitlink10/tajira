<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SeoLandingPage;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SeoLandingPageController extends Controller
{
    public function index()
    {
        $this->authorize('seo.manage');
        $pages = SeoLandingPage::query()->with(['category:id,name', 'brand:id,name'])->latest()->paginate(30);

        return view('admin.seo.landing-pages.index', compact('pages'));
    }

    public function create()
    {
        $this->authorize('seo.manage');

        return view('admin.seo.landing-pages.form', [
            'page' => new SeoLandingPage(['is_active' => true, 'is_indexable' => true]),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer)
    {
        $this->authorize('seo.manage');
        $page = SeoLandingPage::query()->create($this->validated($request, null, $slugs, $sanitizer));
        $audit->log('seo-landing-page.created', $page, [], $page->toArray());

        return redirect()->route('admin.seo.landing-pages.index')->with('success', 'Collection created. Confirm it has a real product selection before indexing.');
    }

    public function edit(SeoLandingPage $landingPage)
    {
        $this->authorize('seo.manage');

        return view('admin.seo.landing-pages.form', [
            'page' => $landingPage,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, SeoLandingPage $landingPage, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer)
    {
        $this->authorize('seo.manage');
        $old = $landingPage->toArray();
        $landingPage->update($this->validated($request, $landingPage, $slugs, $sanitizer));
        $audit->log('seo-landing-page.updated', $landingPage, $old, $landingPage->fresh()->toArray());

        return redirect()->route('admin.seo.landing-pages.index')->with('success', 'Collection updated.');
    }

    public function destroy(SeoLandingPage $landingPage, AuditLogger $audit)
    {
        $this->authorize('seo.manage');
        $audit->log('seo-landing-page.deleted', $landingPage);
        $landingPage->delete();

        return back()->with('success', 'Collection deleted. Add a redirect if the URL was indexed.');
    }

    private function validated(Request $request, ?SeoLandingPage $page, SlugService $slugs, HtmlSanitizer $sanitizer): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:220'],
            'type' => ['required', 'in:brand,category,filter'],
            'intro' => ['required', 'string', 'min:40', 'max:10000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'filters_json' => ['nullable', 'string', 'max:5000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'is_indexable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $filters = [];

        if (filled($data['filters_json'] ?? null)) {
            $filters = json_decode($data['filters_json'], true);

            if (! is_array($filters)) {
                throw ValidationException::withMessages(['filters_json' => 'Filters must be a valid JSON object.']);
            }
        }

        return [
            'title' => $data['title'],
            'slug' => $slugs->unique($data['slug'] ?: $data['title'], SeoLandingPage::class, $page?->id),
            'type' => $data['type'],
            'intro' => $sanitizer->clean($data['intro']),
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'filters' => $filters,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'is_indexable' => $request->boolean('is_indexable'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
