<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:rollover')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('telescope:prune --hours=72')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::call(function (): void {
    $disk = Storage::disk(config('filesystems.default'));

    foreach ($disk->allFiles() as $path) {
        if (! str_ends_with($path, '.tmp') && ! str_ends_with($path, '.tmp.json')) {
            continue;
        }

        // Only reap orphaned ffmpeg outputs (<source>.thumbnail.jpg.<uuid>.tmp
        // and storyboard equivalents); never touch unrelated uploads.
        if (! str_contains($path, '.thumbnail.jpg.') && ! str_contains($path, '.storyboard.jpg.')) {
            continue;
        }

        if (now()->timestamp - $disk->lastModified($path) > 3600) {
            $disk->delete($path);
        }
    }
})->daily()->name('sweep-thumbnail-tmp')->withoutOverlapping()->onOneServer();

// Spatie is pre-migrate safety only (compose setup hard-gates on it).
// Dailies live in restic + plain pg_dump (dedup-friendly). Daily clean
// enforces the 1-week max age on old deploy-time zips.
Schedule::command('backup:clean')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();
