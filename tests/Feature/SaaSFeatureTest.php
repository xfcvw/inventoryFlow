<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CreatesWorkspace;
use Tests\TestCase;

class SaaSFeatureTest extends TestCase
{
    use CreatesWorkspace, RefreshDatabase;

    public function test_owner_can_change_plan_in_local_billing_simulator(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner, 'owner', 'free');
        Sanctum::actingAs($owner);

        $this->postJson('/api/billing/change-plan', ['plan'=>'pro'])->assertOk()->assertJsonPath('plan','pro');
        $this->assertDatabaseHas('workspaces', ['id'=>$workspace->id,'plan'=>'pro']);
    }

    public function test_admin_can_invite_team_member(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner, 'owner', 'pro');
        Sanctum::actingAs($owner);

        $this->postJson('/api/invitations', ['email'=>'new@example.com','role'=>'member'])->assertCreated();
        $this->assertDatabaseHas('invitations', ['workspace_id'=>$workspace->id,'email'=>'new@example.com','role'=>'member']);
    }

    public function test_reports_are_blocked_on_free_plan(): void
    {
        $owner = User::factory()->create();
        $this->createWorkspaceFor($owner, 'owner', 'free');
        Sanctum::actingAs($owner);
        $this->getJson('/api/reports/overview')->assertForbidden();
    }
}
