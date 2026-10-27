<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        if ($request->routeIs('admin.mfa.*', 'admin.logout')) {
            return $next($request);
        }

        if ($user->mfa_enabled) {
            if ($request->session()->get('admin_mfa_passed') === true) {
                return $next($request);
            }

            return redirect()->route('admin.mfa.challenge');
        }

        if (config('tajira.security.require_admin_mfa', false)) {
            return redirect()->route('admin.mfa.setup')
                ->with('warning', 'Two-factor authentication is required before using the administration area.');
        }

        return $next($request);
    }
}
