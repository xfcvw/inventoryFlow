<?php
use App\Http\Controllers\Api\DashboardController; 
use App\Http\Controllers\Api\InventoryController; 
use App\Http\Controllers\Api\OrderController; 
use App\Http\Controllers\Api\ProductController; 
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Lista todos os pedidos
    Route::get('/orders', [OrderController::class, 'index']);

    // Cria um novo pedido
    Route::post('/orders', [OrderController::class, 'store']);

    // Mostra os detalhes de um pedido específico
    Route::get('/orders/{order}', [OrderController::class, 'show']);

});