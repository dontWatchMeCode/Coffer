<?php

use App\Http\Controllers\ActivityHistory\ActivityHistoryController;
use App\Http\Controllers\ApiTokens\ApiTokenController;
use App\Http\Controllers\ApiTokens\ApiTokenPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RecordLinkController;
use App\Http\Controllers\RecordTagController;
use App\Http\Controllers\Search\SearchPageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('team.dashboard');
        Route::get('search', SearchController::class)->middleware('throttle:30,1')->name('team.search');
        Route::get('search/page', SearchPageController::class)->middleware('throttle:30,1')->name('team.search.page');

        Route::get('mcp', [ApiTokenPageController::class, 'index'])->name('team.mcp.index');
        Route::post('mcp', [ApiTokenController::class, 'store'])->middleware('throttle:10,1')->name('team.mcp.store');
        Route::patch('mcp/{mcpToken}', [ApiTokenController::class, 'update'])->whereNumber('mcpToken')->name('team.mcp.update');
        Route::delete('mcp/{mcpToken}', [ApiTokenController::class, 'destroy'])->whereNumber('mcpToken')->name('team.mcp.destroy');

        Route::get('activity-history', [ActivityHistoryController::class, 'index'])->middleware('throttle:60,1')->name('team.activity-history.index');

        Route::get('links/candidates', [RecordLinkController::class, 'candidates'])->middleware('throttle:30,1')->name('team.links.candidates');
        Route::post('links', [RecordLinkController::class, 'store'])->middleware('throttle:60,1')->name('team.links.store');
        Route::delete('links', [RecordLinkController::class, 'destroy'])->middleware('throttle:60,1')->name('team.links.destroy');

        Route::get('tags/candidates', [RecordTagController::class, 'candidates'])->middleware('throttle:30,1')->name('team.tags.candidates');
        Route::post('tags', [RecordTagController::class, 'store'])->middleware('throttle:60,1')->name('team.tags.store');
        Route::delete('tags', [RecordTagController::class, 'destroy'])->middleware('throttle:60,1')->name('team.tags.destroy');

        require __DIR__.'/features/calendar.php';
        require __DIR__.'/features/contacts.php';
        require __DIR__.'/features/bookmarks.php';
        require __DIR__.'/features/subscriptions.php';
        require __DIR__.'/features/log.php';
        require __DIR__.'/features/notes.php';
        require __DIR__.'/features/files.php';
        require __DIR__.'/features/collections.php';
        require __DIR__.'/features/tasks.php';
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}', [TeamInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
