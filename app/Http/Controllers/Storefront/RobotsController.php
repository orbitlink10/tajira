<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(SeoService $seo): Response
    {
        $lines = ['User-agent: *'];

        if ($seo->globalNoIndex()) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /account';
            $lines[] = 'Disallow: /cart';
            $lines[] = 'Disallow: /checkout';
            $lines[] = 'Disallow: /login';
            $lines[] = 'Disallow: /register';
            $lines[] = 'Disallow: /password';
            $lines[] = 'Disallow: /orders';
            $lines[] = 'Disallow: /quote';
            $lines[] = 'Disallow: /search';
            $lines[] = 'Disallow: /payments';
            $lines[] = 'Disallow: /feeds';
            $lines[] = 'Disallow: /*?sort=';
            $lines[] = 'Disallow: /*?price_min=';
            $lines[] = 'Disallow: /*?price_max=';
            $lines[] = 'Disallow: /*?attr';
            $lines[] = '';
            $lines[] = 'Sitemap: '.url('/sitemap.xml');
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
