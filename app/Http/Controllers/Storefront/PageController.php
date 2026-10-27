<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\SeoService;
use App\Services\StructuredDataService;

class PageController extends Controller
{
    public function show(Page $page, SeoService $seo, StructuredDataService $structured)
    {
        abort_unless($page->isPublished(), 404);

        $meta = $seo->forPage($page);
        $crumbs = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => $page->title, 'url' => route('pages.show', $page->slug)],
        ];
        $jsonLd = [$structured->breadcrumbs($crumbs)];

        return view('storefront.pages.show', compact('page', 'meta', 'crumbs', 'jsonLd'));
    }
}
