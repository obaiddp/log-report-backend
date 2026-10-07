<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\SupportLogController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\DashboardController;

// --- RBAC
Route::get('/users', [UserController::class, 'getUsers']);
Route::post('/users', [UserController::class, 'postUsers']);
Route::get('/users/{id}', [UserController::class, 'getUserById']);
Route::put('/users/{id}', [UserController::class, 'updateUserById']);
Route::delete('/users/{id}', [UserController::class, 'deleteUserById']);

Route::get('/roles', [RoleController::class, 'roles']);

Route::get('/permissions', [PermissionController::class, 'getPermissions']);
Route::post('/permissions', [PermissionController::class, 'postPermissions']);
Route::delete('/permissions/{id}', [PermissionController::class, 'deletePermissions']);

Route::get('/roles/{role_id}/permissions', [RolePermissionController::class, 'getRolePermissions']);
Route::put('/roles/{role_id}/permissions', [RolePermissionController::class, 'updateRolePermissions']);

Route::middleware('web')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    // --- auth
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::middleware('permission:user_performance')->group(function () {
        Route::get('/dashboard/user-performance', [DashboardController::class, 'userPerformance']);
    });

    // --- log reports
    Route::get('/support-logs', [SupportLogController::class, 'getSupportLogs']);
    Route::post('/support-logs', [SupportLogController::class, 'postSupportLogs']);
    Route::get('/support-logs/{id}', [SupportLogController::class, 'getSupportLogById']);
    Route::put('/support-logs/{id}', [SupportLogController::class, 'updateSupportLogById']);
    Route::delete('/support-logs/{id}', [SupportLogController::class, 'deleteSupportLogById']);


    // --- departments
    Route::get('/departments', [DepartmentController::class, 'getDepartments']);
    Route::middleware('permission:manage_departments')->group(function () {
        Route::post('/departments', [DepartmentController::class, 'postDepartments']);
        Route::get('/departments/{id}', [DepartmentController::class, 'getDepartmentById']);
        Route::put('/departments/{id}', [DepartmentController::class, 'updateDepartmentById']); 
        Route::delete('/departments/{id}', [DepartmentController::class, 'deleteDepartmentById']);
    });

    // --- items
    Route::get('/items', [ItemController::class, 'getItems']);
    Route::middleware('permission:manage_item_types')->group(function () {
        Route::post('/items', [ItemController::class, 'postItems']);
        Route::get('/items/{id}', [ItemController::class, 'getItemById']);
        Route::put('/items/{id}', [ItemController::class, 'updateItemById']); 
        Route::delete('/items/{id}', [ItemController::class, 'deleteItemById']);
    
    });

    // --- issues
    Route::get('/issues', [IssueController::class, 'getIssues']);
    Route::middleware('permission:manage_issue_types')->group(function () {
        Route::post('/issues', [IssueController::class, 'postIssues']);
        Route::get('/issues/{id}', [IssueController::class, 'getIssueById']);
        Route::put('/issues/{id}', [IssueController::class, 'updateIssueById']); 
        Route::delete('/issues/{id}', [IssueController::class, 'deleteIssueById']);
    });
});