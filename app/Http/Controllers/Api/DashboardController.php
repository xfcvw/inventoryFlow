<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductWarehouseStock;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PlanService $plans): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $products = $workspace->products(); $orders = $workspace->orders(); $movements = $workspace->inventoryMovements();
        $monthOrders = (clone $orders)->where('created_at', '>=', now()->startOfMonth())->where('status', '!=', 'cancelled');
        return response()->json([
            'workspace' => [
                'id' => $workspace->id, 'name' => $workspace->name, 'plan' => $workspace->plan,
                'role' => $request->user()->roleInWorkspace($workspace), 'limits' => $plans->limits($workspace),
                'currency' => $workspace->currency,
            ],
            'total_products' => (clone $products)->count(), 'total_stock' => (int) (clone $products)->sum('stock'),
            'low_stock' => ProductWarehouseStock::where('workspace_id', $workspace->id)->whereColumn('quantity', '<=', 'min_stock')->count(),
            'total_orders' => (clone $orders)->count(), 'month_revenue' => (float) (clone $monthOrders)->sum('total'),
            'month_orders' => (clone $monthOrders)->count(),
            'recent_movements' => (clone $movements)->with(['product:id,name,sku', 'actor:id,name', 'warehouse:id,name,code'])->latest()->limit(8)->get(),
            'recent_orders' => (clone $orders)->with('customerRelation:id,name')->latest()->limit(6)->get(),
            'low_stock_products' => ProductWarehouseStock::where('workspace_id', $workspace->id)->whereColumn('quantity', '<=', 'min_stock')->with(['product:id,name,sku', 'warehouse:id,name,code'])->orderBy('quantity')->limit(8)->get(),
        ]);
    }
}
