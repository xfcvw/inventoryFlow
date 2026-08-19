<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CreatesWorkspace;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use CreatesWorkspace, RefreshDatabase;

    public function test_processing_order_creates_items_and_decreases_stock(): void
    {
        $user = User::factory()->create();
        $workspace = $this->createWorkspaceFor($user);
        $warehouse = $workspace->defaultWarehouse();
        $customer = Customer::create(['workspace_id'=>$workspace->id,'name'=>'Ana']);
        $product = Product::create(['workspace_id'=>$workspace->id,'user_id'=>$user->id,'name'=>'Keyboard','sku'=>'K-1','category'=>'Test','price'=>100,'cost_price'=>50,'stock'=>10,'min_stock'=>2,'active'=>true]);
        ProductWarehouseStock::create(['workspace_id'=>$workspace->id,'product_id'=>$product->id,'warehouse_id'=>$warehouse->id,'quantity'=>10,'min_stock'=>2]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'customer_id'=>$customer->id, 'warehouse_id'=>$warehouse->id, 'status'=>'processing',
            'discount'=>0, 'tax'=>0, 'items'=>[['product_id'=>$product->id,'quantity'=>2,'unit_price'=>100]],
        ])->assertCreated();

        $orderId = $response->json('id');
        $this->assertDatabaseHas('order_items', ['order_id'=>$orderId,'product_id'=>$product->id,'quantity'=>2]);
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id'=>$product->id,'quantity'=>8]);
        $this->assertDatabaseHas('orders', ['id'=>$orderId,'stock_applied'=>true]);
    }

    public function test_cancelling_processed_order_restores_stock(): void
    {
        $user = User::factory()->create();
        $workspace = $this->createWorkspaceFor($user);
        $warehouse = $workspace->defaultWarehouse();
        $product = Product::create(['workspace_id'=>$workspace->id,'user_id'=>$user->id,'name'=>'Mouse','sku'=>'M-9','category'=>'Test','price'=>50,'cost_price'=>25,'stock'=>5,'min_stock'=>1,'active'=>true]);
        ProductWarehouseStock::create(['workspace_id'=>$workspace->id,'product_id'=>$product->id,'warehouse_id'=>$warehouse->id,'quantity'=>5,'min_stock'=>1]);
        Sanctum::actingAs($user);

        $order = $this->postJson('/api/orders', [
            'warehouse_id'=>$warehouse->id,'status'=>'processing','items'=>[['product_id'=>$product->id,'quantity'=>2,'unit_price'=>50]],
        ])->assertCreated()->json();

        $this->putJson('/api/orders/'.$order['id'], ['status'=>'cancelled'])->assertOk();
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id'=>$product->id,'quantity'=>5]);
    }
}
