<?php

use App\Http\Controllers\Bookmarks\BookmarkController;
use App\Http\Controllers\Bookmarks\BookmarkPageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':bookmarks')->group(function () {
    Route::get('bookmarks', [BookmarkPageController::class, 'index'])->name('team.bookmarks.index');
    Route::get('bookmarks/trash', [BookmarkPageController::class, 'trash'])->name('team.bookmarks.trash');
    Route::get('bookmarks/{bookmark}', [BookmarkPageController::class, 'show'])
        ->whereNumber('bookmark')
        ->name('team.bookmarks.show');
    Route::post('bookmarks', [BookmarkController::class, 'store'])->name('team.bookmarks.store');
    Route::patch('bookmarks/{bookmark}', [BookmarkController::class, 'update'])
        ->whereNumber('bookmark')
        ->name('team.bookmarks.update');
    Route::delete('bookmarks/{bookmark}', [BookmarkController::class, 'destroy'])
        ->whereNumber('bookmark')
        ->name('team.bookmarks.destroy');
    Route::patch('bookmarks/{bookmark}/restore', [BookmarkController::class, 'restore'])
        ->whereNumber('bookmark')
        ->name('team.bookmarks.restore');
    Route::delete('bookmarks/{bookmark}/force', [BookmarkController::class, 'forceDestroy'])
        ->whereNumber('bookmark')
        ->name('team.bookmarks.force-destroy');
});
