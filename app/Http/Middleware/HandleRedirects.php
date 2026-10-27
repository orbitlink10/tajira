<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = Redirect::normalisePath($request->path());

        if ($path === '/' || $request->is('admin', 'admin/*', 'account', 'account/*', 'cart', 'checkout', 'payments/*')) {
            return $next($request);
        }

        $visited = [];

        for ($i = 0; $i < 5; $i++) {
            if (isset($visited[$path])) {
                break;
            }

            $visited[$path] = true;
            $redirect = Redirect::query()->where('from_path', $path)->where('is_active', true)->first();

            if (! $redirect) {
                break;
            }

            $redirect->increment('hits');

            if ($redirect->status_code === 410) {
                abort(410);
            }

            $nextPath = Redirect::normalisePath($redirect->to_path);
            $nextRedirect = Redirect::query()->where('from_path', $nextPath)->where('is_active', true)->first();

            if ($nextRedirect && $nextRedirect->status_code === 410) {
                abort(410);
            }

            if ($nextRedirect) {
                $redirect->update(['to_path' => $nextRedirect->to_path]);
                $path = Redirect::normalisePath($nextRedirect->to_path);

                continue;
            }

            $query = $request->getQueryString();

            return redirect()->to($nextPath.($query ? '?'.$query : ''), $redirect->status_code);
        }

        return $next($request);
    }
}
