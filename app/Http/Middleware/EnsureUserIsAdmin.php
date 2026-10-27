<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || ! $user->is_active) {
            if ($request->expectsJson()) {
                abort(403);
            }

            return redirect()->route('admin.login')->with('error', 'Administrator access is required.');
        }

        return $next($request);
    }
}
