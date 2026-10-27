<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class NavigationController extends Controller
{
    public function index()
    {
        $this->authorize('content.manage');
        $items = NavigationItem::query()->orderBy('location')->orderBy('position')->orderBy('label')->get();

        return view('admin.navigation.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('content.manage');
        $parents = NavigationItem::query()->orderBy('label')->get();

        return view('admin.navigation.form', ['item' => new NavigationItem(['is_active' => true, 'location' => 'header']), 'parents' => $parents]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $this->authorize('content.manage');
        $item = NavigationItem::query()->create($this->validated($request, null));
        $audit->log('navigation.created', $item, [], $item->toArray());
        $this->flush();

        return redirect()->route('admin.navigation.index')->with('success', 'Navigation item created.');
    }

    public function edit(NavigationItem $navigation)
    {
        $this->authorize('content.manage');
        $parents = NavigationItem::query()->whereKeyNot($navigation->id)->orderBy('label')->get();

        return view('admin.navigation.form', ['item' => $navigation, 'parents' => $parents]);
    }

    public function update(Request $request, NavigationItem $navigation, AuditLogger $audit)
    {
        $this->authorize('content.manage');
        $old = $navigation->toArray();
        $navigation->update($this->validated($request, $navigation));
        $audit->log('navigation.updated', $navigation, $old, $navigation->fresh()->toArray());
        $this->flush();

        return redirect()->route('admin.navigation.index')->with('success', 'Navigation item updated.');
    }

    public function destroy(NavigationItem $navigation, AuditLogger $audit)
    {
        $this->authorize('content.manage');
        $audit->log('navigation.deleted', $navigation, $navigation->toArray());
        $navigation->delete();
        $this->flush();

        return redirect()->route('admin.navigation.index')->with('success', 'Navigation item deleted.');
    }

    private function validated(Request $request, ?NavigationItem $item): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'url' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', Rule::notIn($item ? [$item->id] : []), 'exists:navigation_items,id'],
            'location' => ['required', 'in:header,footer,utility'],
            'target' => ['required', 'in:_self,_blank'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['position'] = (int) ($data['position'] ?? 0);
        $data['type'] = 'link';
        $data['url'] = $data['url'] ?: '#';

        return $data;
    }

    private function flush(): void
    {
        Cache::forget('tajira.menu.header');
        Cache::forget('tajira.menu.footer');
        Cache::forget('tajira.nav.header');
        Cache::forget('tajira.nav.footer');
    }
}
