<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        /*
        |--------------------------------------------------------------------------
        | Produtos do usuário
        |--------------------------------------------------------------------------
        */

        $productsQuery = Product::query()
            ->where('user_id', $userId);


        /*
        |--------------------------------------------------------------------------
        | Total de produtos
        |--------------------------------------------------------------------------
        */

        $totalProducts = (clone $productsQuery)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Total de unidades em estoque
        |--------------------------------------------------------------------------
        */

        $totalStock = (clone $productsQuery)
            ->sum('stock');


        /*
        |--------------------------------------------------------------------------
        | Produtos com estoque baixo
        |--------------------------------------------------------------------------
        */

        $lowStockProducts = (clone $productsQuery)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->get([
                'id',
                'name',
                'sku',
                'category',
                'stock',
                'min_stock',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Quantidade de produtos com estoque baixo
        |--------------------------------------------------------------------------
        */

        $lowStock = $lowStockProducts->count();


        /*
        |--------------------------------------------------------------------------
        | Total de pedidos
        |--------------------------------------------------------------------------
        */

        $totalOrders = Order::query()
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Movimentações recentes
        |--------------------------------------------------------------------------
        */

        $recentMovements = InventoryMovement::query()
            ->with('product')
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->latest()
            ->limit(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Resposta para o frontend
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'total_products' => $totalProducts,

            'total_stock' => $totalStock,

            'low_stock' => $lowStock,

            'total_orders' => $totalOrders,

            'recent_movements' => $recentMovements,

            'low_stock_products' => $lowStockProducts,
        ]);
    }
}