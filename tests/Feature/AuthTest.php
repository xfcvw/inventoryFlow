<?php
namespace Tests\Feature;
use App\Models\User; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class AuthTest extends TestCase { use RefreshDatabase; public function test_user_can_log_in_and_log_out():void{$u=User::factory()->create(['email'=>'student@example.com','password'=>'secret123']);$this->post('/login',['email'=>'student@example.com','password'=>'secret123'])->assertRedirect('/app');$this->assertAuthenticatedAs($u);$this->post('/logout')->assertRedirect('/login');$this->assertGuest();} }
