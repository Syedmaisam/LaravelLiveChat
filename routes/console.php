<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('visitors:cleanup-stale')->everyMinute();
Schedule::command('chat:cleanup-files')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily file cleanup at 3 AM
Schedule::command('chat:cleanup-files')->dailyAt('03:00');
