<?php

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
        Route::patch('tasks/{task}', [TaskController::class, 'update'])
            ->whereNumber('task')
            ->name('team.tasks.update');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
});

require __DIR__.'/settings.php';
