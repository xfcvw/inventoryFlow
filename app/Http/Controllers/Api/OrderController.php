<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $query = $workspace->orders()->with(['customerRelation:id,name,email', 'warehouse:id,name,code', 'items']);
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn ($q) => $q->where('customer', 'ilike', "%{$search}%")->orWhere('id', is_numeric($search) ? (int) $search : -1));
        }
        if ($request->filled('status')) $query->where('status', $request->query('status'));
        return response()->json($query->latest()->get());
    }

    public function store(Request $request, StockService $stock, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $validated = $this->validated($request, $workspace->id);

        $order = DB::transaction(function () use ($request, $workspace, $validated, $stock) {
            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
            $items = collect($validated['items'])->map(function ($item) use ($workspace) {
                $product = Product::where('workspace_id', $workspace->id)->findOrFail($item['product_id']);
                return ['product' => $product, 'quantity' => (int) $item['quantity'], 'unit_price' => (float) ($item['unit_price'] ?? $product->price)];
            });
            $subtotal = $items->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $discount = (float) ($validated['discount'] ?? 0);
            $tax = (float) ($validated['tax'] ?? 0);
            $total = max(0, $subtotal - $discount + $tax);
            $customerName = $validated['customer'] ?? null;
            if (! empty($validated['customer_id'])) $customerName = $workspace->customers()->findOrFail($validated['customer_id'])->name;

            $order = $workspace->orders()->create([
                'user_id' => $request->user()->id, 'customer_id' => $validated['customer_id'] ?? null,
                'warehouse_id' => $warehouse->id, 'customer' => $customerName ?: 'Walk-in customer',
                'subtotal' => $subtotal, 'discount' => $discount, 'tax' => $tax, 'total' => $total,
                'status' => $validated['status'], 'notes' => $validated['notes'] ?? null,
                'stock_applied' => false,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id, 'product_name' => $item['product']->name,
                    'sku' => $item['product']->sku, 'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'], 'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            if (in_array($order->status, ['processing', 'completed'], true)) {
                $this->applyStock($order, $workspace, $request, $stock);
            }
            return $order;
        });

        $audit->log($request, $workspace, 'order.created', $order, ['total' => $order->total, 'status' => $order->status]);
        return response()->json($order->load(['items', 'customerRelation:id,name,email', 'warehouse:id,name,code']), 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->belongs($request, $order);
        return response()->json($order->load(['items', 'customerRelation', 'warehouse', 'creator:id,name']));
    }

    public function update(Request $request, Order $order, StockService $stock, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $this->belongs($request, $order);
        $validated = $request->validate(['status' => ['required', 'in:pending,processing,completed,cancelled'], 'notes' => ['nullable', 'string', 'max:4000']]);

        DB::transaction(function () use ($request, $workspace, $order, $validated, $stock) {
            $oldStatus = $order->status;
            $order->update(['status' => $validated['status'], 'notes' => $validated['notes'] ?? $order->notes]);

            if (! $order->stock_applied && in_array($order->status, ['processing', 'completed'], true)) {
                $this->applyStock($order, $workspace, $request, $stock);
            }
            if ($order->stock_applied && $order->status === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->restoreStock($order, $workspace, $request, $stock);
            }
        });
        $audit->log($request, $workspace, 'order.status_changed', $order, ['status' => $order->status]);
        return response()->json($order->fresh()->load(['items', 'customerRelation:id,name,email', 'warehouse:id,name,code']));
    }

    public function destroy(Request $request, Order $order, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $this->belongs($request, $order);
        abort_if($order->stock_applied && $order->status !== 'cancelled', 422, 'Cancel the order before deleting it so stock can be restored.');
        $audit->log($request, $workspace, 'order.deleted', $order, ['total' => $order->total]);
        $order->delete();
        return response()->json(['message' => 'Order deleted.']);
    }

    private function validated(Request $request, int $workspaceId): array
    {
        return $request->validate([
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where(fn ($q) => $q->where('workspace_id', $workspaceId))],
            'customer' => ['nullable', 'string', 'max:120'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('workspace_id', $workspaceId))],
            'discount' => ['nullable', 'numeric', 'min:0'], 'tax' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,processing,completed'], 'notes' => ['nullable', 'string', 'max:4000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where(fn ($q) => $q->where('workspace_id', $workspaceId))],
            'items.*.quantity' => ['required', 'integer', 'min:1'], 'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function applyStock(Order $order, $workspace, Request $request, StockService $stock): void
    {
        $warehouse = $order->warehouse;
        foreach ($order->items as $item) {
            if (! $item->product_id) continue;
            $stock->move($workspace, $request->user(), Product::findOrFail($item->product_id), $warehouse, 'out', $item->quantity, 'Order #' . $order->id, 'order', $order->id);
        }
        $order->update(['stock_applied' => true, 'processed_at' => now()]);
    }

    private function restoreStock(Order $order, $workspace, Request $request, StockService $stock): void
    {
        $warehouse = $order->warehouse;
        foreach ($order->items as $item) {
            if (! $item->product_id) continue;
            $stock->move($workspace, $request->user(), Product::findOrFail($item->product_id), $warehouse, 'in', $item->quantity, 'Cancelled order #' . $order->id, 'order_cancel', $order->id);
        }
        $order->update(['stock_applied' => false]);
    }

    private function belongs(Request $request, Order $order): void
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($order->workspace_id === $workspace->id, 404);
    }
}
