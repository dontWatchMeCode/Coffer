<?php

use App\Http\Controllers\Calendar\CalendarEventController;
use App\Http\Controllers\Calendar\CalendarPageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':calendar')->group(function () {
    Route::get('calendar', [CalendarPageController::class, 'index'])->name('team.calendar.index');
    Route::get('calendar/trash', [CalendarPageController::class, 'trash'])->name('team.calendar.trash');
    Route::get('calendar/events/{event}/edit', [CalendarPageController::class, 'edit'])
        ->whereNumber('event')
        ->name('team.calendar.events.edit');
    Route::post('calendar/events', [CalendarEventController::class, 'store'])->name('team.calendar.events.store');
    Route::patch('calendar/events/{event}', [CalendarEventController::class, 'update'])
        ->whereNumber('event')
        ->name('team.calendar.events.update');
    Route::delete('calendar/events/{event}', [CalendarEventController::class, 'destroy'])
        ->whereNumber('event')
        ->name('team.calendar.events.destroy');
    Route::patch('calendar/events/{event}/restore', [CalendarEventController::class, 'restore'])
        ->whereNumber('event')
        ->name('team.calendar.events.restore');
    Route::delete('calendar/events/{event}/force', [CalendarEventController::class, 'forceDestroy'])
        ->whereNumber('event')
        ->name('team.calendar.events.force-destroy');
});
