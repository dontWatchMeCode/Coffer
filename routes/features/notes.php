<?php

use App\Http\Controllers\Notes\NoteController;
use App\Http\Controllers\Notes\NotePageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':notes')->group(function () {
    Route::get('notes', [NotePageController::class, 'index'])->name('team.notes.index');
    Route::get('notes/trash', [NotePageController::class, 'trash'])->name('team.notes.trash');
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
    Route::patch('notes/{note}/restore', [NoteController::class, 'restore'])
        ->whereNumber('note')
        ->name('team.notes.restore');
    Route::delete('notes/{note}/force', [NoteController::class, 'forceDestroy'])
        ->whereNumber('note')
        ->name('team.notes.force-destroy');
});
