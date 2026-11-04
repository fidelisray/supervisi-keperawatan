<?php

use App\Http\Controllers\SupervisionReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SupervisionReportController::class, 'create'])->name('home');

Route::prefix('supervisi')->name('supervisi.')->group(function () {
    Route::get('/', [SupervisionReportController::class, 'index'])->name('index');
    Route::get('/create', [SupervisionReportController::class, 'create'])->name('create');
    Route::post('/', [SupervisionReportController::class, 'store'])->name('store');
    Route::get('/{supervisionReport}', [SupervisionReportController::class, 'show'])->name('show');
});
