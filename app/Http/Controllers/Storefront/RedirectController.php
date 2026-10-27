<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $requested = Redirect::normalisePath('/'.ltrim($request->path(), '/'));
        $current = $requested;
        $visited = [];

        for ($i = 0; $i < 6; $i++) {
            if (isset($visited[$current])) {
                break;
            }

            $visited[$current] = true;
            $redirect = Redirect::query()->where('from_path', $current)->where('is_active', true)->first();

            if (! $redirect) {
                break;
            }

            if ($redirect->status_code === 410) {
                abort(410);
            }

            $redirect->increment('hits');

            if (! isset($visited[$redirect->to_path])) {
                $next = Redirect::query()->where('from_path', $redirect->to_path)->where('is_active', true)->first();

                if ($next && $next->status_code !== 410) {
                    $redirect->update(['to_path' => $next->to_path]);

                    return redirect()->to($next->to_path, $redirect->status_code);
                }
            }

            return redirect()->to($redirect->to_path, $redirect->status_code);
        }

        $meta = [
            'title' => 'Page not found | '.setting('business.name', 'Tajira'),
            'description' => 'The page you requested could not be found.',
            'canonical' => url()->current(),
            'robots' => 'noindex,follow',
            'image' => asset('images/og-default.png'),
            'type' => 'website',
            'previous' => null,
            'next' => null,
            'gsc_verification' => setting('seo.gsc_verification'),
        ];

        return response()->view('errors.404', compact('meta'), 404);
    }
}
