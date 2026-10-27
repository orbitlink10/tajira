<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Services\AuditLogger;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index()
    {
        $this->authorize('catalogue.view');
        $attributes = Attribute::query()->withCount(['values', 'productValues'])->orderBy('position')->orderBy('name')->get();

        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        $this->authorize('catalogue.manage');

        return view('admin.attributes.form', ['attribute' => new Attribute(['type' => 'select', 'is_filterable' => true, 'is_comparable' => true])]);
    }

    public function store(Request $request, SlugService $slugs, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        $data = $this->validated($request, null, $slugs);
        $valuesText = $data['values_text'] ?? '';
        unset($data['values_text']);
        $attribute = Attribute::query()->create($data);
        $this->syncValues($attribute, $valuesText, $slugs);
        $audit->log('attribute.created', $attribute, [], $attribute->toArray());

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute created.');
    }

    public function edit(Attribute $attribute)
    {
        $this->authorize('catalogue.manage');
        $attribute->load('values');

        return view('admin.attributes.form', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute, SlugService $slugs, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');
        $old = $attribute->toArray();
        $data = $this->validated($request, $attribute, $slugs);
        $valuesText = $data['values_text'] ?? '';
        unset($data['values_text']);
        $attribute->update($data);
        $this->syncValues($attribute, $valuesText, $slugs);
        $audit->log('attribute.updated', $attribute, $old, $attribute->fresh()->toArray());

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated. Check affected products if values changed.');
    }

    public function destroy(Attribute $attribute, AuditLogger $audit)
    {
        $this->authorize('catalogue.manage');

        if ($attribute->productValues()->exists()) {
            return back()->with('error', 'This attribute is used by products. Remove it from those products first.');
        }

        $audit->log('attribute.deleted', $attribute, $attribute->toArray());
        $attribute->delete();

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted.');
    }

    private function validated(Request $request, ?Attribute $attribute, SlugService $slugs): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140'],
            'code' => ['nullable', 'string', 'max:80'],
            'type' => ['required', 'in:select,number,boolean,text'],
            'unit' => ['nullable', 'string', 'max:30'],
            'group' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:320'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_comparable' => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
            'values_text' => ['nullable', 'string', 'max:20000'],
        ]);

        $data['slug'] = $slugs->unique($data['slug'] ?: $data['name'], Attribute::class, $attribute?->id);
        $data['code'] = $data['code'] ?: Str::upper(Str::slug($data['name'], '_'));
        $data['is_filterable'] = $request->boolean('is_filterable');
        $data['is_comparable'] = $request->boolean('is_comparable');
        $data['is_searchable'] = $request->boolean('is_searchable');
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }

    private function syncValues(Attribute $attribute, string $text, SlugService $slugs): void
    {
        if ($attribute->type !== 'select') {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $seen = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $value = $parts[0];

            if ($value === '') {
                continue;
            }

            $existing = $attribute->values()->where('value', $value)->first();
            $base = Str::slug($value) ?: 'value';
            $slug = $base;
            $counter = 2;

            while ($attribute->values()->where('slug', $slug)->when($existing, fn ($query) => $query->whereKeyNot($existing->id))->exists()) {
                $slug = $base.'-'.$counter;
                $counter++;
            }

            $seen[] = $value;

            $payload = [
                'value' => $value,
                'slug' => $existing?->slug ?? $slug,
                'numeric_value' => isset($parts[1]) && is_numeric($parts[1]) ? (float) $parts[1] : null,
                'unit' => $parts[2] ?? null,
                'position' => $index,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                $attribute->values()->create($payload);
            }
        }

        $attribute->values()->whereNotIn('value', $seen)->delete();
    }
}
