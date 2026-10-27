<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\AuditLogger;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function index()
    {
        $this->authorize('content.manage');
        $sections = HomepageSection::query()->orderBy('position')->get()->keyBy('key');

        return view('admin.homepage.index', compact('sections'));
    }

    public function update(Request $request, AuditLogger $audit, HtmlSanitizer $sanitizer)
    {
        $this->authorize('content.manage');

        $data = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.title' => ['nullable', 'string', 'max:200'],
            'sections.*.subtitle' => ['nullable', 'string', 'max:400'],
            'sections.*.body' => ['nullable', 'string', 'max:20000'],
            'sections.*.position' => ['nullable', 'integer', 'min:0'],
            'sections.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($data['sections'] as $key => $values) {
            $section = HomepageSection::query()->firstOrCreate(['key' => $key], ['title' => ucfirst(str_replace('_', ' ', $key))]);
            $old = $section->toArray();
            $section->update([
                'title' => $values['title'] ?? null,
                'subtitle' => $values['subtitle'] ?? null,
                'body' => $sanitizer->clean($values['body'] ?? null),
                'position' => (int) ($values['position'] ?? 0),
                'is_active' => (bool) ($values['is_active'] ?? false),
            ]);
            $audit->log('homepage.section.updated', $section, $old, $section->fresh()->toArray());
        }

        return back()->with('success', 'Homepage sections updated.');
    }
}
