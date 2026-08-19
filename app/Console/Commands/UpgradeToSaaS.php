<?php

namespace App\Console\Commands;

use App\Models\ProductWarehouseStock;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpgradeToSaaS extends Command
{
    protected $signature = 'inventoryflow:upgrade-saas';
    protected $description = 'Upgrades older InventoryFlow data to the complete SaaS workspace model.';

    public function handle(): int
    {
        User::query()->orderBy('id')->each(function (User $user): void {
            DB::transaction(function () use ($user): void {
                $workspace = $user->workspaces()->first();

                if (! $workspace) {
                    $workspace = Workspace::create([
                        'name' => $user->name . "'s Workspace",
                        'slug' => $this->uniqueSlug($user->name . '-workspace'),
                        'owner_id' => $user->id,
                        'plan' => 'free',
                        'currency' => 'BRL',
                        'locale' => 'pt-BR',
                        'timezone' => 'America/Sao_Paulo',
                    ]);
                    $workspace->users()->attach($user->id, ['role' => 'owner']);
                }

                $user->forceFill(['current_workspace_id' => $workspace->id])->save();

                $user->products()->whereNull('workspace_id')->update(['workspace_id' => $workspace->id]);
                $user->orders()->whereNull('workspace_id')->update(['workspace_id' => $workspace->id]);
                $user->inventoryMovements()->whereNull('workspace_id')->update(['workspace_id' => $workspace->id]);

                $warehouse = Warehouse::firstOrCreate(
                    ['workspace_id' => $workspace->id, 'code' => 'MAIN'],
                    ['name' => 'Main Warehouse', 'is_default' => true]
                );

                Subscription::firstOrCreate(
                    ['workspace_id' => $workspace->id, 'status' => 'active'],
                    ['plan' => $workspace->plan ?: 'free', 'provider' => 'local']
                );

                $workspace->inventoryMovements()->whereNull('warehouse_id')->update(['warehouse_id' => $warehouse->id]);
                $workspace->orders()->whereNull('warehouse_id')->update(['warehouse_id' => $warehouse->id]);

                $workspace->products()->each(function ($product) use ($workspace, $warehouse): void {
                    ProductWarehouseStock::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                        [
                            'workspace_id' => $workspace->id,
                            'quantity' => (int) $product->stock,
                            'min_stock' => (int) $product->min_stock,
                        ]
                    );
                });
            });

            $this->line("Upgraded user #{$user->id} ({$user->email})");
        });

        $this->info('InventoryFlow complete SaaS upgrade finished.');
        return self::SUCCESS;
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'workspace';
        $slug = $base;
        $suffix = 2;

        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
