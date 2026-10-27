<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use App\Services\RevisionService;
use App\Services\SeoService;
use App\Services\SlugService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('content.view');
        $pages = Page::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('position')
            ->orderBy('title')
            ->paginate(30)
            ->withQueryString();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        $this->authorize('content.manage');

        return view('admin.pages.form', ['page' => new Page(['status' => 'draft', 'is_indexable' => true])]);
    }

    public function store(Request $request, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer, RevisionService $revisions)
    {
        $this->authorize('content.manage');
        $data = $this->validated($request, null, $slugs, $sanitizer);
        $page = Page::query()->create($data);
        $revisions->record($page, $request->user(), 'Created');
        $audit->log('page.created', $page, [], $page->toArray());

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page)
    {
        $this->authorize('content.manage');

        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer, RevisionService $revisions)
    {
        $this->authorize('content.manage');
        $old = $page->toArray();
        $oldSlug = $page->slug;
        $data = $this->validated($request, $page, $slugs, $sanitizer);
        $page->update($data);

        if ($oldSlug !== $page->slug) {
            $slugs->handleSlugChange($page, $oldSlug, '/pages/'.$page->slug, '/pages/'.$oldSlug);
        }

        $revisions->record($page, $request->user(), 'Updated');
        $audit->log('page.updated', $page, $old, $page->fresh()->toArray());

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page, AuditLogger $audit)
    {
        $this->authorize('content.manage');
        $audit->log('page.deleted', $page, $page->toArray());
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    public function preview(Page $page, SeoService $seo)
    {
        $this->authorize('content.view');
        $meta = $seo->forPage($page);
        $meta['robots'] = 'noindex,nofollow';
        $crumbs = [['name' => 'Home', 'url' => '/'], ['name' => $page->title, 'url' => '#']];
        $jsonLd = [];
        $preview = true;

        return view('storefront.pages.show', compact('page', 'meta', 'crumbs', 'jsonLd', 'preview'));
    }

    private function validated(Request $request, ?Page $page, SlugService $slugs, HtmlSanitizer $sanitizer): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:200000'],
            'template' => ['required', 'in:default,policy,contact,wide'],
            'status' => ['required', 'in:draft,pending_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_override' => ['nullable', 'url', 'max:255'],
            'robots' => ['nullable', 'string', 'max:60'],
            'is_indexable' => ['nullable', 'boolean'],
            'show_in_footer' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = $slugs->unique($data['slug'] ?: $data['title'], Page::class, $page?->id);
        $data['body'] = $sanitizer->clean($data['body'] ?? null);
        $data['is_indexable'] = $request->boolean('is_indexable');
        $data['show_in_footer'] = $request->boolean('show_in_footer');
        $data['position'] = (int) ($data['position'] ?? 0);
        $data['published_at'] = $data['status'] === 'published'
            ? ($request->input('published_at') ?: $page?->published_at ?: now())
            : ($request->input('published_at') ?: null);
        $data['updated_by'] = $request->user()->id;

        if (! $page) {
            $data['created_by'] = $request->user()->id;
        }

        return $data;
    }
}
