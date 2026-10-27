<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RedirectController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('seo.manage');
        $redirects = Redirect::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $q->where('from_path', 'like', $term)->orWhere('to_path', 'like', $term);
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.redirects.index', compact('redirects'));
    }

    public function create()
    {
        $this->authorize('seo.manage');

        return view('admin.redirects.form', ['redirect' => new Redirect(['status_code' => 301, 'is_active' => true])]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $this->authorize('seo.manage');
        $redirect = Redirect::query()->create($this->validated($request, null));
        $audit->log('redirect.created', $redirect, [], $redirect->toArray());

        return redirect()->route('admin.redirects.index')->with('success', 'Redirect created.');
    }

    public function edit(Redirect $redirect)
    {
        $this->authorize('seo.manage');

        return view('admin.redirects.form', compact('redirect'));
    }

    public function update(Request $request, Redirect $redirect, AuditLogger $audit)
    {
        $this->authorize('seo.manage');
        $old = $redirect->toArray();
        $redirect->update($this->validated($request, $redirect));
        $audit->log('redirect.updated', $redirect, $old, $redirect->fresh()->toArray());

        return redirect()->route('admin.redirects.index')->with('success', 'Redirect updated.');
    }

    public function destroy(Redirect $redirect, AuditLogger $audit)
    {
        $this->authorize('seo.manage');
        $audit->log('redirect.deleted', $redirect, $redirect->toArray());
        $redirect->delete();

        return redirect()->route('admin.redirects.index')->with('success', 'Redirect deleted.');
    }

    private function validated(Request $request, ?Redirect $redirect): array
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:500'],
            'to_path' => ['required', 'string', 'max:500'],
            'status_code' => ['required', 'in:301,302,410'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $from = Redirect::normalisePath($data['from_path']);
        $to = $data['status_code'] == 410 ? '' : Redirect::normalisePath($data['to_path']);

        if ($from === $to && $data['status_code'] != 410) {
            throw ValidationException::withMessages(['to_path' => 'A redirect cannot point to itself.']);
        }

        if (Redirect::query()->where('from_path', $from)->when($redirect, fn ($q) => $q->whereKeyNot($redirect->id))->exists()) {
            throw ValidationException::withMessages(['from_path' => 'A redirect already exists for this path.']);
        }

        if ($to !== '' && Redirect::query()->where('to_path', $from)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['from_path' => 'This would create a redirect loop. Update the existing redirect instead.']);
        }

        return [
            'from_path' => $from,
            'to_path' => $to,
            'status_code' => (int) $data['status_code'],
            'is_active' => $request->boolean('is_active'),
            'note' => $data['note'] ?? null,
        ];
    }
}
