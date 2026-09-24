<?php

use App\Http\Controllers\AssetController;
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

Route::apiResource('departments', DepartmentController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('technical-personnel', TechnicalPersonnelController::class)
    ->parameters(['technical-personnel' => 'technical_personnel']);
Route::apiResource('assets', AssetController::class);
Route::apiResource('inspections', InspectionController::class);

Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
