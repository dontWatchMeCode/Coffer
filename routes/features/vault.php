<?php

use App\Http\Controllers\Vault\VaultController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':e2ee')->group(function (): void {
    Route::get('vault/keys', [VaultController::class, 'keys'])->name('team.vault.keys');
    Route::post('vault/keys/register', [VaultController::class, 'registerKey'])->name('team.vault.keys.register');
    Route::post('vault/keys/envelopes', [VaultController::class, 'putEnvelopes'])->name('team.vault.keys.envelopes');
    Route::post('vault/keys/rotate', [VaultController::class, 'rotate'])->name('team.vault.keys.rotate');
    Route::post('vault/pull', [VaultController::class, 'pull'])->middleware('throttle:60,1')->name('team.vault.pull');
    Route::post('vault/push', [VaultController::class, 'push'])->middleware('throttle:60,1')->name('team.vault.push');
    Route::post('vault/files', [VaultController::class, 'filePut'])->middleware('throttle:30,1')->name('team.vault.files.put');
    Route::get('vault/files/{file}', [VaultController::class, 'fileGet'])
        ->whereNumber('file')
        ->middleware('throttle:30,1')
        ->name('team.vault.files.get');
});
