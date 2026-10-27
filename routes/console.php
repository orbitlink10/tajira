<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('stock:release-expired')->everyMinute()->withoutOverlapping();
Schedule::command('payments:reconcile')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('products:publish-scheduled')->everyMinute()->withoutOverlapping();
