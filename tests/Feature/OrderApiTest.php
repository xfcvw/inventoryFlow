<?php
namespace Tests\Feature;
use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Laravel\Sanctum\Sanctum; use Tests\TestCase;
class OrderApiTest extends TestCase { use RefreshDatabase; public function test_user_can_create_and_update_order_status():void{$u=User::factory()->create();Sanctum::actingAs($u);$order=$this->postJson('/api/orders',['customer'=>'Ana Costa','total'=>199.90,'status'=>'pending'])->assertCreated()->json();$this->putJson('/api/orders/'.$order['id'],['status'=>'processing'])->assertOk()->assertJsonPath('status','processing');} }
