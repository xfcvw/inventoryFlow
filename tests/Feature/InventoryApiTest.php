<?php
namespace Tests\Feature;
use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Laravel\Sanctum\Sanctum; use Tests\TestCase;
class InventoryApiTest extends TestCase { use RefreshDatabase;
 public function test_stock_cannot_become_negative():void{$u=User::factory()->create();Sanctum::actingAs($u);$p=$u->products()->create(['name'=>'Mouse','sku'=>'M-1','category'=>'Peripherals','price'=>100,'stock'=>2,'min_stock'=>1]);$this->postJson('/api/inventory/movements',['product_id'=>$p->id,'type'=>'out','quantity'=>3])->assertUnprocessable();$this->assertDatabaseHas('products',['id'=>$p->id,'stock'=>2]);}
 public function test_stock_entry_updates_product_and_history():void{$u=User::factory()->create();Sanctum::actingAs($u);$p=$u->products()->create(['name'=>'Mouse','sku'=>'M-2','category'=>'Peripherals','price'=>100,'stock'=>2,'min_stock'=>1]);$this->postJson('/api/inventory/movements',['product_id'=>$p->id,'type'=>'in','quantity'=>5])->assertCreated();$this->assertDatabaseHas('products',['id'=>$p->id,'stock'=>7]);$this->assertDatabaseHas('inventory_movements',['user_id'=>$u->id,'product_id'=>$p->id,'type'=>'in','quantity'=>5]);}
}
