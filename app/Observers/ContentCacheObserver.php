<?php

namespace App\Observers;

use App\Services\SitemapService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ContentCacheObserver
{
    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    public function restored(Model $model): void
    {
        $this->flush($model);
    }

    private function flush(Model $model): void
    {
        Cache::forget('tajira.menu.header');
        Cache::forget('tajira.menu.footer');
        Cache::forget('tajira.nav.header');
        Cache::forget('tajira.nav.footer');
        Cache::forget('tajira.brands.active');
        Cache::forget('tajira.home.sections');
        Cache::forget('tajira.home.featured');
        Cache::forget('tajira.home.categories');
        Cache::forget('tajira.footer.pages');

        app(SitemapService::class)->flush();
    }
}
