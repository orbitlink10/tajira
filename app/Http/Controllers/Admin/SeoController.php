<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LinkAuditService;
use App\Services\SeoAuditService;

class SeoController extends Controller
{
    public function index(SeoAuditService $seo)
    {
        $this->authorize('seo.manage');
        $metrics = $seo->metrics();
        $problems = $seo->problemProducts();
        $duplicates = $seo->duplicateTitleGroups();
        $linkReport = $seo->brokenLinksReport();

        return view('admin.seo.index', compact('metrics', 'problems', 'duplicates', 'linkReport'));
    }

    public function auditLinks(LinkAuditService $audit)
    {
        $this->authorize('seo.manage');
        $report = $audit->run();

        return back()->with('success', 'Link audit checked '.$report['checked'].' internal link(s) and found '.count($report['issues']).' issue(s).');
    }
}
