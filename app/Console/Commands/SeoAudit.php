<?php

namespace App\Console\Commands;

use App\Services\LinkAuditService;
use App\Services\SeoAuditService;
use Illuminate\Console\Command;

class SeoAudit extends Command
{
    protected $signature = 'seo:audit {--links : Also run the internal link audit}';

    protected $description = 'Report catalogue SEO issues and optionally audit internal links';

    public function handle(SeoAuditService $seo, LinkAuditService $links): int
    {
        $metrics = $seo->metrics();

        foreach ($metrics as $key => $value) {
            $this->line(str_pad(str_replace('_', ' ', $key), 34).': '.$value);
        }

        if ($this->option('links')) {
            $report = $links->run();
            $this->info('Link audit checked '.$report['checked'].' link(s); issues: '.count($report['issues']).'.');
        }

        return self::SUCCESS;
    }
}
