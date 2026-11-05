<?php

use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Auth\SsoAuthController;
use App\Http\Controllers\SupervisionAnalyticsController;
use App\Http\Controllers\SupervisionReportController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [SsoAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [SsoAuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SsoAuthController::class, 'logout'])->name('logout');

    Route::get('/', [SupervisionReportController::class, 'create'])->name('home');

    Route::prefix('supervisi')->name('supervisi.')->group(function () {
        Route::get('/', [SupervisionReportController::class, 'index'])->name('index');
        Route::get('/analytics', [SupervisionAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/create', [SupervisionReportController::class, 'create'])->name('create');
        Route::post('/', [SupervisionReportController::class, 'store'])->name('store');
        Route::get('/{supervisionReport}', [SupervisionReportController::class, 'show'])->name('show');
        Route::get('/{supervisionReport}/edit', [SupervisionReportController::class, 'edit'])->name('edit');
        Route::put('/{supervisionReport}', [SupervisionReportController::class, 'update'])->name('update');
        Route::patch('/{supervisionReport}/incidents/{incident}', [SupervisionReportController::class, 'updateIncidentStatus'])->name('incidents.update-status');
    });

    Route::prefix('admin')->name('admin.')->middleware(EnsureUserIsAdmin::class)->group(function () {
        Route::get('/roles', [RoleManagementController::class, 'index'])->name('roles.index');
        Route::post('/roles/definitions', [RoleManagementController::class, 'storeRole'])->name('roles.store-definition');
        Route::delete('/roles/definitions/{roleId}', [RoleManagementController::class, 'destroyRole'])->name('roles.destroy-definition');
        Route::post('/roles/users', [RoleManagementController::class, 'assignUserRole'])->name('roles.assign-user');
        Route::patch('/roles/users/{appRole}', [RoleManagementController::class, 'updateUserRole'])->name('roles.update-user');
        Route::delete('/roles/users/{appRole}', [RoleManagementController::class, 'destroyUserRole'])->name('roles.destroy-user');
    });
});
