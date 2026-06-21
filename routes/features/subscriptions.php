<?php

use App\Http\Controllers\Subscriptions\SubscriptionCategoryController;
use App\Http\Controllers\Subscriptions\SubscriptionController;
use App\Http\Controllers\Subscriptions\SubscriptionInsightsController;
use App\Http\Controllers\Subscriptions\SubscriptionPageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':subscriptions')->group(function () {
    Route::get('subscriptions/categories/candidates', [SubscriptionCategoryController::class, 'candidates'])->middleware('throttle:30,1')->name('team.subscriptions.categories.candidates');

    Route::get('subscriptions/insights', [SubscriptionInsightsController::class, 'index'])->name('team.subscriptions.insights');
    Route::get('subscriptions', [SubscriptionPageController::class, 'index'])->name('team.subscriptions.index');
    Route::get('subscriptions/trash', [SubscriptionPageController::class, 'trash'])->name('team.subscriptions.trash');
    Route::get('subscriptions/{subscription}', [SubscriptionPageController::class, 'show'])
        ->whereNumber('subscription')
        ->name('team.subscriptions.show');
    Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('team.subscriptions.store');
    Route::patch('subscriptions/{subscription}', [SubscriptionController::class, 'update'])
        ->whereNumber('subscription')
        ->name('team.subscriptions.update');
    Route::delete('subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])
        ->whereNumber('subscription')
        ->name('team.subscriptions.destroy');
    Route::patch('subscriptions/{subscription}/restore', [SubscriptionController::class, 'restore'])
        ->whereNumber('subscription')
        ->name('team.subscriptions.restore');
    Route::delete('subscriptions/{subscription}/force', [SubscriptionController::class, 'forceDestroy'])
        ->whereNumber('subscription')
        ->name('team.subscriptions.force-destroy');
});
