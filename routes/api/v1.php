<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\ReportController;
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

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('auth.login');

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::get('/user', [AuthController::class, 'me'])->name('user');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
    Route::apiResource('users', UserController::class)->only(['index', 'show']);
    Route::apiResource('technical-personnel', TechnicalPersonnelController::class)
        ->only(['index', 'show'])
        ->parameters(['technical-personnel' => 'technical_personnel']);
    Route::apiResource('assets', AssetController::class)->only(['index', 'show']);
    Route::apiResource('inspections', InspectionController::class)->only(['index', 'show']);

    Route::middleware('role:admin')->group(function (): void {
        Route::apiResource('departments', DepartmentController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('users', UserController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('technical-personnel', TechnicalPersonnelController::class)
            ->only(['store', 'update', 'destroy'])
            ->parameters(['technical-personnel' => 'technical_personnel']);
        Route::apiResource('assets', AssetController::class)->only(['store', 'update', 'destroy']);
    });

    Route::middleware('role:admin,technician')->group(function (): void {
        Route::apiResource('inspections', InspectionController::class)->only(['store', 'update', 'destroy']);
        Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });
});
