<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CreatesWorkspace;
use Tests\TestCase;

class WorkspaceApiTest extends TestCase
{
    use CreatesWorkspace, RefreshDatabase;

    public function test_user_can_switch_between_workspaces_they_belong_to(): void
    {
        $user = User::factory()->create();
        $first = $this->createWorkspaceFor($user);

        $second = Workspace::create([
            'name' => 'Second Workspace',
            'slug' => 'second-workspace',
            'owner_id' => $user->id,
            'plan' => 'free',
        ]);
        $second->users()->attach($user->id, ['role' => 'owner']);

        Sanctum::actingAs($user);

        $this->postJson('/api/workspaces/' . $second->id . '/switch')
            ->assertOk()
            ->assertJsonPath('workspace_id', $second->id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_workspace_id' => $second->id,
        ]);
    }

    public function test_user_cannot_switch_to_an_unrelated_workspace(): void
    {
        $user = User::factory()->create();
        $this->createWorkspaceFor($user);

        $other = User::factory()->create();
        $otherWorkspace = $this->createWorkspaceFor($other);

        Sanctum::actingAs($user);

        $this->postJson('/api/workspaces/' . $otherWorkspace->id . '/switch')
            ->assertNotFound();
    }
}
