<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\SupportLogController;


Route::get('/roles', [RoleController::class, 'roles']);


Route::middleware('web')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('/users', [UserController::class, 'users']);
    Route::get('/departments', [DepartmentController::class, 'getDepartments']);
    Route::get('/items', [ItemController::class, 'getItems']);
    Route::get('/issues', [IssueController::class, 'getIssues']);

    // --- log reports
    Route::get('/support-logs', [SupportLogController::class, 'getSupportLogs']);
    Route::post('/support-logs', [SupportLogController::class, 'postSupportLogs']);
    Route::get('/support-logs/{id}', [SupportLogController::class, 'getSupportLogById']);
    Route::put('/support-logs/{id}', [SupportLogController::class, 'updateSupportLogById']);
    Route::delete('/support-logs/{id}', [SupportLogController::class, 'deleteSupportLogById']);

    Route::middleware('permission:manage_departments')->group(function () {
    
        // --- departments
        Route::post('/departments', [DepartmentController::class, 'postDepartments']);
        Route::get('/departments/{id}', [DepartmentController::class, 'getDepartmentById']);
        Route::put('/departments/{id}', [DepartmentController::class, 'updateDepartmentById']); 
        Route::delete('/departments/{id}', [DepartmentController::class, 'deleteDepartmentById']);

        // --- items
        Route::post('/items', [ItemController::class, 'postItems']);
        Route::get('/items/{id}', [ItemController::class, 'getItemById']);
        Route::put('/items/{id}', [ItemController::class, 'updateItemById']); 
        Route::delete('/items/{id}', [ItemController::class, 'deleteItemById']);

        // --- issues
        Route::post('/issues', [IssueController::class, 'postIssues']);
        Route::get('/issues/{id}', [IssueController::class, 'getIssueById']);
        Route::put('/issues/{id}', [IssueController::class, 'updateIssueById']); 
        Route::delete('/issues/{id}', [IssueController::class, 'deleteIssueById']);
    });
});