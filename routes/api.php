<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (Request $request) => $request->user()->only(['id', 'name', 'email', 'current_workspace_id']));
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

    Route::middleware('workspace')->group(function () {
        Route::get('/workspace', [WorkspaceController::class, 'show']);
        Route::put('/workspace', [WorkspaceController::class, 'update'])->middleware('workspace.role:owner,admin');
        Route::get('/dashboard', DashboardController::class);

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::post('/products', [ProductController::class, 'store'])->middleware('workspace.role:owner,admin,manager');
        Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('workspace.role:owner,admin,manager');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('workspace.role:owner,admin');

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('workspace.role:owner,admin,manager');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('workspace.role:owner,admin,manager');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('workspace.role:owner,admin');

        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('workspace.role:owner,admin,manager');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('workspace.role:owner,admin,manager');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('workspace.role:owner,admin');

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store'])->middleware('workspace.role:owner,admin,manager,member');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('workspace.role:owner,admin,manager,member');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('workspace.role:owner,admin,manager');

        Route::get('/warehouses', [WarehouseController::class, 'index']);
        Route::post('/warehouses', [WarehouseController::class, 'store'])->middleware('workspace.role:owner,admin');
        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->middleware('workspace.role:owner,admin');
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->middleware('workspace.role:owner,admin');

        Route::get('/inventory/stock', [InventoryController::class, 'stock']);
        Route::get('/inventory/movements', [InventoryController::class, 'index']);
        Route::post('/inventory/movements', [InventoryController::class, 'store'])->middleware('workspace.role:owner,admin,manager,member');

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store'])->middleware('workspace.role:owner,admin,manager,member');
        Route::put('/orders/{order}', [OrderController::class, 'update'])->middleware('workspace.role:owner,admin,manager,member');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->middleware('workspace.role:owner,admin,manager');

        Route::get('/team', [TeamController::class, 'index'])->middleware('workspace.role:owner,admin');
        Route::put('/team/{user}', [TeamController::class, 'update'])->middleware('workspace.role:owner,admin');
        Route::delete('/team/{user}', [TeamController::class, 'destroy'])->middleware('workspace.role:owner,admin');
        Route::get('/invitations', [InvitationController::class, 'index'])->middleware('workspace.role:owner,admin');
        Route::post('/invitations', [InvitationController::class, 'store'])->middleware('workspace.role:owner,admin');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->middleware('workspace.role:owner,admin');

        Route::get('/billing', [BillingController::class, 'show'])->middleware('workspace.role:owner');
        Route::post('/billing/change-plan', [BillingController::class, 'change'])->middleware('workspace.role:owner');
        Route::get('/reports/overview', [ReportController::class, 'overview'])->middleware('workspace.role:owner,admin,manager');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('workspace.role:owner,admin');
    });
});
