<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectStaging
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('tajira.staging.enabled')) {
            return $next($request);
        }

        $username = (string) config('tajira.staging.username');
        $password = (string) config('tajira.staging.password');

        if ($username !== '' && $password !== '') {
            $providedUser = (string) $request->getUser();
            $providedPassword = (string) $request->getPassword();

            if (! hash_equals($username, $providedUser) || ! hash_equals($password, $providedPassword)) {
                return response('Staging access is protected.', 401, [
                    'WWW-Authenticate' => 'Basic realm="Tajira staging"',
                    'X-Robots-Tag' => 'noindex, nofollow',
                ]);
            }
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
