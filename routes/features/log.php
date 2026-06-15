<?php

use App\Http\Controllers\Log\LogEntryController;
use App\Http\Controllers\Log\LogPageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':log')->group(function () {
    Route::get('log', [LogPageController::class, 'index'])->name('team.log.index');
    Route::get('log/trash', [LogPageController::class, 'trash'])->name('team.log.trash');
    Route::post('log', [LogEntryController::class, 'store'])->name('team.log.store');
    Route::delete('log/{logEntry}', [LogEntryController::class, 'destroy'])
        ->whereNumber('logEntry')
        ->name('team.log.destroy');
    Route::patch('log/{logEntry}/restore', [LogEntryController::class, 'restore'])
        ->whereNumber('logEntry')
        ->name('team.log.restore');
    Route::delete('log/{logEntry}/force', [LogEntryController::class, 'forceDestroy'])
        ->whereNumber('logEntry')
        ->name('team.log.force-destroy');
});
