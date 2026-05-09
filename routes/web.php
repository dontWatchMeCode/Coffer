<?php

use App\Http\Controllers\ApiTokens\ApiTokenController;
use App\Http\Controllers\ApiTokens\ApiTokenPageController;
use App\Http\Controllers\Bookmarks\BookmarkController;
use App\Http\Controllers\Bookmarks\BookmarkPageController;
use App\Http\Controllers\Calendar\CalendarEventController;
use App\Http\Controllers\Calendar\CalendarPageController;
use App\Http\Controllers\Collections\CollectionController;
use App\Http\Controllers\Collections\CollectionPageController;
use App\Http\Controllers\Contacts\ContactController;
use App\Http\Controllers\Contacts\ContactPageController;
use App\Http\Controllers\Notes\NoteController;
use App\Http\Controllers\Notes\NotePageController;
use App\Http\Controllers\RecordLinkController;
use App\Http\Controllers\RecordTagController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Tasks\ProjectController;
use App\Http\Controllers\Tasks\TaskCommentController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Tasks\TaskPageController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::inertia('dashboard', 'Dashboard')->name('team.dashboard');
        Route::get('search', SearchController::class)->middleware('throttle:30,1')->name('team.search');

        Route::get('mcp', [ApiTokenPageController::class, 'index'])->name('team.mcp.index');
        Route::post('mcp', [ApiTokenController::class, 'store'])->middleware('throttle:10,1')->name('team.mcp.store');
        Route::patch('mcp/{mcpToken}', [ApiTokenController::class, 'update'])->whereNumber('mcpToken')->name('team.mcp.update');
        Route::delete('mcp/{mcpToken}', [ApiTokenController::class, 'destroy'])->whereNumber('mcpToken')->name('team.mcp.destroy');

        Route::get('links/candidates', [RecordLinkController::class, 'candidates'])->middleware('throttle:30,1')->name('team.links.candidates');
        Route::post('links', [RecordLinkController::class, 'store'])->middleware('throttle:60,1')->name('team.links.store');
        Route::delete('links', [RecordLinkController::class, 'destroy'])->middleware('throttle:60,1')->name('team.links.destroy');

        Route::get('tags/candidates', [RecordTagController::class, 'candidates'])->middleware('throttle:30,1')->name('team.tags.candidates');
        Route::post('tags', [RecordTagController::class, 'store'])->middleware('throttle:60,1')->name('team.tags.store');
        Route::delete('tags', [RecordTagController::class, 'destroy'])->middleware('throttle:60,1')->name('team.tags.destroy');

        Route::get('calendar', [CalendarPageController::class, 'index'])->name('team.calendar.index');
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

        Route::get('contacts', [ContactPageController::class, 'index'])->name('team.contacts.index');
        Route::get('contacts/{contact}', [ContactPageController::class, 'show'])
            ->whereNumber('contact')
            ->name('team.contacts.show');
        Route::post('contacts', [ContactController::class, 'store'])->name('team.contacts.store');
        Route::patch('contacts/{contact}', [ContactController::class, 'update'])
            ->whereNumber('contact')
            ->name('team.contacts.update');
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])
            ->whereNumber('contact')
            ->name('team.contacts.destroy');

        Route::get('bookmarks', [BookmarkPageController::class, 'index'])->name('team.bookmarks.index');
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

        Route::get('notes', [NotePageController::class, 'index'])->name('team.notes.index');
        Route::get('notes/{note}', [NotePageController::class, 'show'])
            ->whereNumber('note')
            ->name('team.notes.show');
        Route::post('notes', [NoteController::class, 'store'])->name('team.notes.store');
        Route::patch('notes/{note}', [NoteController::class, 'update'])
            ->whereNumber('note')
            ->name('team.notes.update');
        Route::delete('notes/{note}', [NoteController::class, 'destroy'])
            ->whereNumber('note')
            ->name('team.notes.destroy');

        Route::get('collections', [CollectionPageController::class, 'index'])->name('team.collections.index');
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

        Route::get('tasks', [TaskPageController::class, 'index'])->name('team.tasks.index');
        Route::get('tasks/{project}', [TaskPageController::class, 'show'])
            ->whereNumber('project')
            ->name('team.tasks.show');
        Route::get('tasks/{project}/{task}/edit', [TaskPageController::class, 'edit'])
            ->whereNumber(['project', 'task'])
            ->name('team.tasks.edit');
        Route::post('tasks/projects', [ProjectController::class, 'store'])->name('team.tasks.projects.store');
        Route::patch('tasks/projects/{project}', [ProjectController::class, 'update'])
            ->whereNumber('project')
            ->name('team.tasks.projects.update');
        Route::post('tasks', [TaskController::class, 'store'])->name('team.tasks.store');
        Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])
            ->whereNumber('task')
            ->name('team.tasks.comments.store');
        Route::patch('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'update'])
            ->whereNumber(['task', 'comment'])
            ->name('team.tasks.comments.update');
        Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])
            ->whereNumber(['task', 'comment'])
            ->name('team.tasks.comments.destroy');
        Route::patch('tasks/{task}', [TaskController::class, 'update'])
            ->whereNumber('task')
            ->name('team.tasks.update');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])
            ->whereNumber('task')
            ->name('team.tasks.destroy');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}', [TeamInvitationController::class, 'show'])->name('invitations.show');
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
