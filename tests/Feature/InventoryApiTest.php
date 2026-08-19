<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CreatesWorkspace;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use CreatesWorkspace, RefreshDatabase;

    public function test_stock_cannot_become_negative(): void
    {
        $user = User::factory()->create();
        $workspace = $this->createWorkspaceFor($user);
        $warehouse = $workspace->defaultWarehouse();
        $product = Product::create(['workspace_id'=>$workspace->id,'user_id'=>$user->id,'name'=>'Mouse','sku'=>'M-1','category'=>'Test','price'=>100,'cost_price'=>50,'stock'=>2,'min_stock'=>1,'active'=>true]);
        ProductWarehouseStock::create(['workspace_id'=>$workspace->id,'product_id'=>$product->id,'warehouse_id'=>$warehouse->id,'quantity'=>2,'min_stock'=>1]);
        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/movements', ['product_id'=>$product->id,'warehouse_id'=>$warehouse->id,'type'=>'out','quantity'=>3])
            ->assertUnprocessable();
        $this->assertDatabaseHas('products', ['id'=>$product->id,'stock'=>2]);
    }

    public function test_inventory_movement_updates_warehouse_and_total_stock(): void
    {
        $user = User::factory()->create();
        $workspace = $this->createWorkspaceFor($user);
        $warehouse = $workspace->defaultWarehouse();
        $product = Product::create(['workspace_id'=>$workspace->id,'user_id'=>$user->id,'name'=>'Mouse','sku'=>'M-2','category'=>'Test','price'=>100,'cost_price'=>50,'stock'=>2,'min_stock'=>1,'active'=>true]);
        ProductWarehouseStock::create(['workspace_id'=>$workspace->id,'product_id'=>$product->id,'warehouse_id'=>$warehouse->id,'quantity'=>2,'min_stock'=>1]);
        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/movements', ['product_id'=>$product->id,'warehouse_id'=>$warehouse->id,'type'=>'in','quantity'=>5,'reason'=>'Purchase'])
            ->assertCreated();
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id'=>$product->id,'quantity'=>7]);
        $this->assertDatabaseHas('products', ['id'=>$product->id,'stock'=>7]);
        $this->assertDatabaseHas('inventory_movements', ['workspace_id'=>$workspace->id,'user_id'=>$user->id,'product_id'=>$product->id,'warehouse_id'=>$warehouse->id,'quantity'=>5]);
    }
}
