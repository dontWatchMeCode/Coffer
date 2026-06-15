<?php

use App\Http\Controllers\Contacts\ContactController;
use App\Http\Controllers\Contacts\ContactPageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':contacts')->group(function () {
    Route::get('contacts', [ContactPageController::class, 'index'])->name('team.contacts.index');
    Route::get('contacts/trash', [ContactPageController::class, 'trash'])->name('team.contacts.trash');
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
    Route::patch('contacts/{contact}/restore', [ContactController::class, 'restore'])
        ->whereNumber('contact')
        ->name('team.contacts.restore');
    Route::delete('contacts/{contact}/force', [ContactController::class, 'forceDestroy'])
        ->whereNumber('contact')
        ->name('team.contacts.force-destroy');
});
