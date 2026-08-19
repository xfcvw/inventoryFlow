<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Workspace;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function ensureStockRow(Workspace $workspace, Product $product, Warehouse $warehouse): ProductWarehouseStock
    {
        return ProductWarehouseStock::firstOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
            ['workspace_id' => $workspace->id, 'quantity' => 0, 'min_stock' => $product->min_stock]
        );
    }

    public function move(
        Workspace $workspace,
        User $user,
        Product $product,
        Warehouse $warehouse,
        string $type,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        return DB::transaction(function () use (
            $workspace, $user, $product, $warehouse, $type, $quantity, $reason, $referenceType, $referenceId
        ) {
            abort_unless($product->workspace_id === $workspace->id, 404);
            abort_unless($warehouse->workspace_id === $workspace->id, 404);

            $stock = ProductWarehouseStock::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = $this->ensureStockRow($workspace, $product, $warehouse);
                $stock = ProductWarehouseStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
            }

            if ($type === 'out' && $quantity > $stock->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['There is not enough stock in the selected warehouse.'],
                ]);
            }

            $stock->quantity = $type === 'in'
                ? $stock->quantity + $quantity
                : $stock->quantity - $quantity;
            $stock->save();

            $product->stock = (int) ProductWarehouseStock::where('product_id', $product->id)->sum('quantity');
            $product->save();

            $movement = $workspace->inventoryMovements()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'balance_after' => $stock->quantity,
            ]);

            if ($stock->quantity <= $stock->min_stock) {
                $stock->load(['product', 'warehouse']);
                $workspace->users()->wherePivotIn('role', ['owner', 'admin', 'manager'])->get()->each(
                    fn ($member) => $member->notify(new LowStockNotification($stock))
                );
            }

            return $movement;
        });
    }
}
