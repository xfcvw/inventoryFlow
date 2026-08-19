<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | CSRF nos testes
        |--------------------------------------------------------------------------
        |
        | O InventoryFlow continua protegido por CSRF normalmente.
        |
        | Aqui estamos desativando a proteção SOMENTE enquanto
        | este teste automatizado estiver sendo executado.
        |
        */

        $this->withoutMiddleware(
            PreventRequestForgery::class
        );
    }

    public function test_user_can_register_and_receives_a_workspace(): void
    {
        $response = $this->post('/register', [
            'name' => 'Student',
            'email' => 'student@example.com',
            'workspace_name' => 'Student Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/app');

        $this->assertAuthenticated();

        $user = User::where(
            'email',
            'student@example.com'
        )->firstOrFail();

        $this->assertNotNull(
            $user->current_workspace_id
        );

        $this->assertDatabaseHas(
            'workspaces',
            [
                'name' => 'Student Store',
            ]
        );

        $this->assertDatabaseHas(
            'user_workspace',
            [
                'user_id' => $user->id,
                'workspace_id' => $user->current_workspace_id,
                'role' => 'owner',
            ]
        );
    }

    public function test_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->post('/login', [
            'email' => 'student@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/app');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');

        $this->assertGuest();
    }
}