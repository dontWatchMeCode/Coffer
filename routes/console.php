<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:rollover')->dailyAt('00:00');

// Spatie is pre-migrate safety only (compose setup hard-gates on it).
// Dailies live in restic + plain pg_dump (dedup-friendly). Daily clean
// enforces the 1-week max age on old deploy-time zips.
Schedule::command('backup:clean')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();
