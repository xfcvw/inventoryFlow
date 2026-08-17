<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Usuário autenticado
    |--------------------------------------------------------------------------
    */

    Route::get('/me', function (Request $request) {
        return $request->user()->only([
            'id',
            'name',
            'email',
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    );


    /*
    |--------------------------------------------------------------------------
    | Produtos
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/products',
        [ProductController::class, 'index']
    );

    Route::post(
        '/products',
        [ProductController::class, 'store']
    );

    Route::put(
        '/products/{product}',
        [ProductController::class, 'update']
    );

    Route::delete(
        '/products/{product}',
        [ProductController::class, 'destroy']
    );


    /*
    |--------------------------------------------------------------------------
    | Estoque / Movimentações
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/inventory/movements',
        [InventoryController::class, 'index']
    );

    Route::post(
        '/inventory/movements',
        [InventoryController::class, 'store']
    );


    /*
    |--------------------------------------------------------------------------
    | Pedidos
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/orders',
        [OrderController::class, 'index']
    );

    Route::post(
        '/orders',
        [OrderController::class, 'store']
    );

    Route::get(
        '/orders/{order}',
        [OrderController::class, 'show']
    );

});