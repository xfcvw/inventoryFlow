<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Workspace;
use App\Services\AuditService;
use App\Services\PlanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');
        $query = $workspace->products()->with(['categoryRelation:id,name', 'supplier:id,name', 'warehouseStocks.warehouse:id,name,code']);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn (Builder $q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('sku', 'ilike', "%{$search}%")->orWhere('barcode', 'ilike', "%{$search}%"));
        }
        if ($request->filled('category_id')) $query->where('category_id', $request->integer('category_id'));
        if ($request->filled('active')) $query->where('active', filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN));

        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request, PlanService $plans, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        if (! $plans->canCreateProduct($workspace)) throw ValidationException::withMessages(['plan' => ['Product limit reached for this plan.']]);
        $validated = $this->validated($request, $workspace);

        $product = DB::transaction(function () use ($request, $workspace, $validated) {
            $product = $workspace->products()->create([
                ...$validated,
                'category' => $this->categoryName($workspace, $validated['category_id'] ?? null),
                'user_id' => $request->user()->id,
                'stock' => 0,
            ]);

            $warehouse = $workspace->defaultWarehouse();
            if ($warehouse) {
                ProductWarehouseStock::create([
                    'workspace_id' => $workspace->id, 'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id, 'quantity' => (int) ($request->integer('initial_stock') ?: 0),
                    'min_stock' => $product->min_stock,
                ]);
                $product->update(['stock' => (int) ($request->integer('initial_stock') ?: 0)]);
            }
            return $product;
        });

        $audit->log($request, $workspace, 'product.created', $product);
        return response()->json($product->load(['categoryRelation:id,name', 'supplier:id,name', 'warehouseStocks.warehouse:id,name,code']), 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->belongs($request, $product);
        return response()->json($product->load(['categoryRelation', 'supplier', 'warehouseStocks.warehouse']));
    }

    public function update(Request $request, Product $product, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $this->belongs($request, $product);
        $validated = $this->validated($request, $workspace, $product);
        $product->update([...$validated, 'category' => $this->categoryName($workspace, $validated['category_id'] ?? null)]);
        $product->warehouseStocks()->update(['min_stock' => $product->min_stock]);
        $audit->log($request, $workspace, 'product.updated', $product);
        return response()->json($product->fresh()->load(['categoryRelation:id,name', 'supplier:id,name', 'warehouseStocks.warehouse:id,name,code']));
    }

    public function destroy(Request $request, Product $product, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $this->belongs($request, $product);
        abort_if($product->orderItems()->exists(), 422, 'Products used in orders should be deactivated instead of deleted.');
        $audit->log($request, $workspace, 'product.deleted', $product, ['name' => $product->name, 'sku' => $product->sku]);
        $product->delete();
        return response()->json(['message' => 'Product deleted.']);
    }

    private function validated(Request $request, Workspace $workspace, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($product?->id)->where(fn ($q) => $q->where('workspace_id', $workspace->id))],
            'barcode' => ['nullable', 'string', 'max:80'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('workspace_id', $workspace->id))],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('workspace_id', $workspace->id))],
            'description' => ['nullable', 'string', 'max:4000'],
            'price' => ['required', 'numeric', 'min:0'], 'cost_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'], 'active' => ['boolean'],
            'initial_stock' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function categoryName(Workspace $workspace, ?int $id): string
    {
        return $id ? (string) $workspace->categories()->whereKey($id)->value('name') : 'Uncategorized';
    }

    private function belongs(Request $request, Product $product): void
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($product->workspace_id === $workspace->id, 404);
    }
}
