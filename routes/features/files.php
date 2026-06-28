<?php

use App\Http\Controllers\Files\FileController;
use App\Http\Controllers\Files\FilePageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':files')->group(function (): void {
    Route::get('files', [FilePageController::class, 'index'])->name('team.files.index');
    Route::get('files/trash', [FilePageController::class, 'trash'])->name('team.files.trash');
    Route::get('files/{file}', [FilePageController::class, 'show'])
        ->whereNumber('file')
        ->name('team.files.show');
    Route::get('files/{file}/inline', [FileController::class, 'inline'])
        ->whereNumber('file')
        ->name('team.files.inline');
    Route::get('files/{file}/download', [FileController::class, 'download'])
        ->whereNumber('file')
        ->name('team.files.download');
    Route::post('files', [FileController::class, 'store'])->name('team.files.store');
    Route::patch('files/{file}', [FileController::class, 'update'])
        ->whereNumber('file')
        ->name('team.files.update');
    Route::delete('files/{file}', [FileController::class, 'destroy'])
        ->whereNumber('file')
        ->name('team.files.destroy');
    Route::patch('files/{file}/restore', [FileController::class, 'restore'])
        ->whereNumber('file')
        ->name('team.files.restore');
    Route::delete('files/{file}/force', [FileController::class, 'forceDestroy'])
        ->whereNumber('file')
        ->name('team.files.force-destroy');
});
