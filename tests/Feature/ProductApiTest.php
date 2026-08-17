<?php
namespace Tests\Feature;
use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Laravel\Sanctum\Sanctum; use Tests\TestCase;
class ProductApiTest extends TestCase { use RefreshDatabase;
 public function test_authenticated_user_can_create_product():void{$u=User::factory()->create();Sanctum::actingAs($u);$this->postJson('/api/products',['name'=>'Mechanical Keyboard','sku'=>'KB-100','category'=>'Peripherals','price'=>299.90,'stock'=>10,'min_stock'=>3])->assertCreated()->assertJsonPath('name','Mechanical Keyboard');$this->assertDatabaseHas('products',['user_id'=>$u->id,'sku'=>'KB-100','stock'=>10]);}
 public function test_user_cannot_see_another_users_products():void{$owner=User::factory()->create();$other=User::factory()->create();$owner->products()->create(['name'=>'Private Product','sku'=>'PRIVATE-1','category'=>'Test','price'=>10,'stock'=>1,'min_stock'=>0]);Sanctum::actingAs($other);$this->getJson('/api/products')->assertOk()->assertJsonCount(0);}
}
