<?php

use App\Http\Controllers\Collections\CollectionController;
use App\Http\Controllers\Collections\CollectionPageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':collections')->group(function () {
    Route::get('collections', [CollectionPageController::class, 'index'])->name('team.collections.index');
    Route::get('collections/trash', [CollectionPageController::class, 'trash'])->name('team.collections.trash');
    Route::get('collections/{collection}', [CollectionPageController::class, 'show'])
        ->whereNumber('collection')
        ->name('team.collections.show');
    Route::post('collections', [CollectionController::class, 'store'])->name('team.collections.store');
    Route::patch('collections/{collection}', [CollectionController::class, 'update'])
        ->whereNumber('collection')
        ->name('team.collections.update');
    Route::delete('collections/{collection}', [CollectionController::class, 'destroy'])
        ->whereNumber('collection')
        ->name('team.collections.destroy');
    Route::patch('collections/{collection}/restore', [CollectionController::class, 'restore'])
        ->whereNumber('collection')
        ->name('team.collections.restore');
    Route::delete('collections/{collection}/force', [CollectionController::class, 'forceDestroy'])
        ->whereNumber('collection')
        ->name('team.collections.force-destroy');
});
