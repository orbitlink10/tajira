<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('audit.view');

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('event'), fn ($q) => $q->where('event', 'like', '%'.$request->query('event').'%'))
            ->when($request->filled('user'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('email', 'like', '%'.$request->query('user').'%')))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }
}
