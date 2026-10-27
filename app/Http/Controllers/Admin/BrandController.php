<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $this->authorize('catalogue.view');
        $brands = Brand::query()->withCount('products')->orderBy('name')->get();

        return view('admin.brands.index', compact('brands'));
    }

    public function create()
    {
        $this->authorize('catalogue.manage');

        return view('admin.brands.form', ['brand' => new Brand(['is_active' => true, 'is_indexable' => true])]);
    }

    public function store(Request $request, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer)
    {
        $this->authorize('catalogue.manage');
        $data = $this->validated($request, null, $slugs, $sanitizer);
        $brand = Brand::query()->create($data);

        if ($request->hasFile('logo')) {
            $result = app(ImageService::class)->convertToWebp(
                $request->file('logo')->getRealPath(),
                'brands',
                Str::slug($brand->name).'-'.Str::lower(Str::random(6))
            );
            $brand->update(['logo_path' => $result['path']]);
        }

        $audit->log('brand.created', $brand, [], $brand->toArray());

        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand)
    {
        $this->authorize('catalogue.manage');

        return view('admin.brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand, SlugService $slugs, AuditLogger $audit, HtmlSanitizer $sanitizer)
    {
        $this->authorize('catalogue.manage');
        $old = $brand->toArray();
        $oldSlug = $brand->slug;
        $data = $this->validated($request, $brand, $slugs, $sanitizer);
        $brand->update($data);

        if ($oldSlug !== $brand->slug) {
            $slugs->handleSlugChange($brand, $oldSlug, '/brands/'.$brand->slug, '/brands/'.$oldSlug);
        }

        $audit->log('brand.updated', $brand, $old, $brand->fresh()->toArray());

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');

        if ($brand->products()->exists()) {
            return back()->with('error', 'This brand still has products. Reassign them before deleting the brand.');
        }

        $audit->log('brand.deleted', $brand, $brand->toArray());
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted.');
    }

    private function validated(Request $request, ?Brand $brand, SlugService $slugs, HtmlSanitizer $sanitizer): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'intro' => ['nullable', 'string', 'max:20000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_override' => ['nullable', 'url', 'max:255'],
            'is_indexable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'logo' => ['nullable', 'image', 'max:4096', 'mimes:jpg,jpeg,png,webp'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        unset($data['logo'], $data['remove_logo']);
        $data['description'] = isset($data['description']) ? strip_tags($data['description']) : null;
        $data['intro'] = $sanitizer->clean($data['intro'] ?? null);
        $data['slug'] = $slugs->unique($data['slug'] ?: $data['name'], Brand::class, $brand?->id);
        $data['is_indexable'] = $request->boolean('is_indexable');
        $data['is_active'] = $request->boolean('is_active');
        $data['position'] = (int) ($data['position'] ?? 0);

        if ($request->hasFile('logo') && $brand) {
            $result = app(ImageService::class)->convertToWebp(
                $request->file('logo')->getRealPath(),
                'brands',
                Str::slug($data['name']).'-'.Str::lower(Str::random(6))
            );
            $data['logo_path'] = $result['path'];
        }

        if ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        }

        return $data;
    }
}
