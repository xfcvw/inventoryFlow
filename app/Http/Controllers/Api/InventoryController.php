<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $query = $workspace->inventoryMovements()->with(['product:id,name,sku', 'actor:id,name', 'warehouse:id,name,code']);
        if ($request->filled('product_id')) $query->where('product_id', $request->integer('product_id'));
        if ($request->filled('warehouse_id')) $query->where('warehouse_id', $request->integer('warehouse_id'));
        return response()->json($query->latest()->limit(250)->get());
    }

    public function stock(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $rows = \App\Models\ProductWarehouseStock::query()
            ->where('workspace_id', $workspace->id)
            ->with(['product:id,name,sku,min_stock,active', 'warehouse:id,name,code'])
            ->orderBy('warehouse_id')->orderBy('product_id')->get();
        return response()->json($rows);
    }

    public function store(Request $request, StockService $stock, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $validated = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where(fn ($q) => $q->where('workspace_id', $workspace->id))],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('workspace_id', $workspace->id))],
            'type' => ['required', 'in:in,out'], 'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:160'],
        ]);
        $movement = $stock->move(
            $workspace, $request->user(), Product::findOrFail($validated['product_id']), Warehouse::findOrFail($validated['warehouse_id']),
            $validated['type'], $validated['quantity'], $validated['reason'] ?? 'Manual inventory movement'
        );
        $audit->log($request, $workspace, 'inventory.moved', $movement, $validated);
        return response()->json($movement->load(['product:id,name,sku', 'actor:id,name', 'warehouse:id,name,code']), 201);
    }
}
