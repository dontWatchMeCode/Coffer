<?php

use App\Http\Controllers\Spreadsheets\SpreadsheetController;
use App\Http\Controllers\Spreadsheets\SpreadsheetPageController;
use App\Http\Middleware\EnsureTeamFeatureEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureTeamFeatureEnabled::class.':spreadsheets')->group(function () {
    Route::get('spreadsheets', [SpreadsheetPageController::class, 'index'])->name('team.spreadsheets.index');
    Route::get('spreadsheets/trash', [SpreadsheetPageController::class, 'trash'])->name('team.spreadsheets.trash');
    Route::post('spreadsheets', [SpreadsheetController::class, 'store'])->name('team.spreadsheets.store');
    Route::get('spreadsheets/{spreadsheet}', [SpreadsheetPageController::class, 'show'])->whereNumber('spreadsheet')->name('team.spreadsheets.show');
    Route::patch('spreadsheets/{spreadsheet}/workbook', [SpreadsheetController::class, 'saveWorkbook'])->whereNumber('spreadsheet')->name('team.spreadsheets.workbook.update');
    Route::delete('spreadsheets/{spreadsheet}', [SpreadsheetController::class, 'destroy'])->whereNumber('spreadsheet')->name('team.spreadsheets.destroy');
    Route::patch('spreadsheets/{spreadsheet}/restore', [SpreadsheetController::class, 'restore'])->whereNumber('spreadsheet')->name('team.spreadsheets.restore');
    Route::delete('spreadsheets/{spreadsheet}/force', [SpreadsheetController::class, 'forceDestroy'])->whereNumber('spreadsheet')->name('team.spreadsheets.force-destroy');
});
