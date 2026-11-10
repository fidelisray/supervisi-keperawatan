<?php

use App\Http\Controllers\Admin\IncidentCategoryController;
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
        Route::get('/analytics/drilldown', [SupervisionAnalyticsController::class, 'drilldown'])->name('analytics.drilldown');
        Route::get('/analytics/export', [SupervisionAnalyticsController::class, 'export'])->name('analytics.export');
        Route::get('/create', [SupervisionReportController::class, 'create'])->name('create');
        Route::post('/', [SupervisionReportController::class, 'store'])->name('store');
        Route::get('/{supervisionReport}', [SupervisionReportController::class, 'show'])->name('show');
        Route::get('/{supervisionReport}/pdf', [SupervisionReportController::class, 'exportPdf'])->name('pdf');
        Route::get('/{supervisionReport}/edit', [SupervisionReportController::class, 'edit'])->name('edit');
        Route::put('/{supervisionReport}', [SupervisionReportController::class, 'update'])->name('update');
        Route::patch('/{supervisionReport}/incidents/{incident}', [SupervisionReportController::class, 'updateIncidentStatus'])->name('incidents.update-status');
    });

    Route::prefix('admin')->name('admin.')->middleware(EnsureUserIsAdmin::class)->group(function () {
        Route::get('/roles', [RoleManagementController::class, 'index'])->name('roles.index');
        Route::post('/roles/definitions', [RoleManagementController::class, 'storeRole'])->name('roles.store-definition');
        Route::put('/roles/definitions/{roleId}/permissions', [RoleManagementController::class, 'updateRolePermissions'])->name('roles.update-permissions');
        Route::delete('/roles/definitions/{roleId}', [RoleManagementController::class, 'destroyRole'])->name('roles.destroy-definition');
        Route::post('/roles/users', [RoleManagementController::class, 'assignUserRole'])->name('roles.assign-user');
        Route::patch('/roles/users/{appRole}', [RoleManagementController::class, 'updateUserRole'])->name('roles.update-user');
        Route::delete('/roles/users/{appRole}', [RoleManagementController::class, 'destroyUserRole'])->name('roles.destroy-user');

        Route::get('/incident-categories', [IncidentCategoryController::class, 'index'])->name('incident-categories.index');
        Route::post('/incident-categories', [IncidentCategoryController::class, 'store'])->name('incident-categories.store');
        Route::put('/incident-categories/{incidentCategory}', [IncidentCategoryController::class, 'update'])->name('incident-categories.update');
        Route::patch('/incident-categories/{incidentCategory}/toggle-active', [IncidentCategoryController::class, 'toggleActive'])->name('incident-categories.toggle-active');
        Route::delete('/incident-categories/{incidentCategory}', [IncidentCategoryController::class, 'destroy'])->name('incident-categories.destroy');
    });
});
