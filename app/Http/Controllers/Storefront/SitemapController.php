<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapService $sitemaps) {}

    public function index(): Response
    {
        return $this->xml($this->sitemaps->index());
    }

    public function pages(): Response
    {
        return $this->xml($this->sitemaps->pages());
    }

    public function categories(): Response
    {
        return $this->xml($this->sitemaps->categories());
    }

    public function brands(): Response
    {
        return $this->xml($this->sitemaps->brands());
    }

    public function products(): Response
    {
        return $this->xml($this->sitemaps->products());
    }

    public function articles(): Response
    {
        return $this->xml($this->sitemaps->articles());
    }

    public function collections(): Response
    {
        return $this->xml($this->sitemaps->collections());
    }

    private function xml(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
