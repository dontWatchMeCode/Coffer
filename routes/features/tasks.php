<?php

use App\Http\Controllers\Tasks\ProjectController;
use App\Http\Controllers\Tasks\TaskCommentController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Tasks\TaskInsightsController;
use App\Http\Controllers\Tasks\TaskPageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':tasks')->group(function () {
    Route::get('tasks/insights', [TaskInsightsController::class, 'index'])->name('team.tasks.insights');
    Route::get('tasks', [TaskPageController::class, 'index'])->name('team.tasks.index');
    Route::get('tasks/{project}', [TaskPageController::class, 'show'])
        ->whereNumber('project')
        ->name('team.tasks.show');
    Route::get('tasks/{project}/trash', [TaskPageController::class, 'trash'])
        ->whereNumber('project')
        ->name('team.tasks.trash');
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
    Route::patch('tasks/{task}/restore', [TaskController::class, 'restore'])
        ->whereNumber('task')
        ->name('team.tasks.restore');
    Route::delete('tasks/{task}/force', [TaskController::class, 'forceDestroy'])
        ->whereNumber('task')
        ->name('team.tasks.force-destroy');
});
