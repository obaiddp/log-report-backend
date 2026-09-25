<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\IssueTypeController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupportLogController;
use App\Http\Controllers\SupportLogLookupController;
use App\Http\Controllers\SupportLogReportController;
use App\Http\Controllers\TechnicalPersonnelController;
use App\Http\Controllers\UserController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
    ]);
})->name('health');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login');
});

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });

    Route::apiResource('support-logs', SupportLogController::class);
    Route::get('/support-log-options', [SupportLogLookupController::class, 'index'])
        ->name('support-log-options');

    // Authenticated users need safe directory reads for forms and filters.
    // Mutations remain admin-only in the group below.
    Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
    Route::apiResource('users', UserController::class)->only(['index', 'show']);
    Route::apiResource('technical-personnel', TechnicalPersonnelController::class)
        ->only(['index', 'show'])
        ->parameters(['technical-personnel' => 'technical_personnel']);
    Route::apiResource('issue-types', IssueTypeController::class)->only(['index', 'show']);
    Route::apiResource('item-types', ItemTypeController::class)->only(['index', 'show']);

    Route::middleware('can:manageDirectory')->group(function (): void {
        Route::apiResource('departments', DepartmentController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('users', UserController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('technical-personnel', TechnicalPersonnelController::class)
            ->only(['store', 'update', 'destroy'])
            ->parameters(['technical-personnel' => 'technical_personnel']);
    });

    Route::middleware('can:manageConfig')->group(function (): void {
        Route::apiResource('issue-types', IssueTypeController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('item-types', ItemTypeController::class)->only(['store', 'update', 'destroy']);
    });

    Route::middleware('can:viewAnalytics')->group(function (): void {
        Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
        Route::get('/reports/by-department', [SupportLogReportController::class, 'byDepartment'])->name('reports.by-department');
        Route::get('/reports/by-resource', [SupportLogReportController::class, 'byResource'])->name('reports.by-resource');
        Route::get('/reports/by-issue-type', [SupportLogReportController::class, 'byIssueType'])->name('reports.by-issue-type');
        Route::get('/reports/by-item', [SupportLogReportController::class, 'byItem'])->name('reports.by-item');
        Route::get('/reports/by-status', [SupportLogReportController::class, 'byStatus'])->name('reports.by-status');
        Route::get('/reports/export', [SupportLogReportController::class, 'export'])->name('reports.export');
    });

    /*
     * Deprecated asset-inspection endpoints remain available for historical
     * data and existing clients, but are now authenticated and should not be
     * used for new support-log workflows.
     */
    Route::apiResource('assets', AssetController::class);
    Route::apiResource('inspections', InspectionController::class);

    Route::get('/reports/summary', [ReportController::class, 'summary'])
        ->middleware('can:viewAnalytics')
        ->name('reports.legacy-summary');
    Route::get('/reports/legacy/export', [ReportController::class, 'export'])
        ->middleware('can:viewAnalytics')
        ->name('reports.legacy-export');
});
