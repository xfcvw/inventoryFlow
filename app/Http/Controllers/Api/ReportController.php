<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\ProductWarehouseStock;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function overview(Request $request, PlanService $plans): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($plans->reportAccess($workspace), 403, 'Reports are available on Pro and Business plans.');

        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();
        $orders = $workspace->orders()->whereBetween('created_at', [$from, $to]);

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.workspace_id', $workspace->id)
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select('order_items.product_name', 'order_items.sku', DB::raw('SUM(order_items.quantity) as units'), DB::raw('SUM(order_items.subtotal) as revenue'))
            ->groupBy('order_items.product_name', 'order_items.sku')
            ->orderByDesc('units')->limit(10)->get();

        $lowStock = ProductWarehouseStock::query()
            ->where('workspace_id', $workspace->id)->whereColumn('quantity', '<=', 'min_stock')
            ->with(['product:id,name,sku', 'warehouse:id,name,code'])->orderBy('quantity')->get();

        return response()->json([
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'sales' => [
                'orders' => (clone $orders)->where('status', '!=', 'cancelled')->count(),
                'revenue' => (float) (clone $orders)->where('status', '!=', 'cancelled')->sum('total'),
                'average_ticket' => (float) ((clone $orders)->where('status', '!=', 'cancelled')->avg('total') ?? 0),
            ],
            'top_products' => $topProducts,
            'low_stock' => $lowStock,
            'inventory_value' => (float) $workspace->products()->selectRaw('COALESCE(SUM(stock * cost_price), 0) as value')->value('value'),
        ]);
    }
}
