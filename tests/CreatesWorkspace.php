<?php

namespace Tests;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Workspace;

trait CreatesWorkspace
{
    protected function createWorkspaceFor(User $user, string $role = 'owner', string $plan = 'pro'): Workspace
    {
        $workspace = Workspace::create([
            'name' => 'Test Workspace ' . $user->id,
            'slug' => 'test-workspace-' . $user->id . '-' . uniqid(),
            'owner_id' => $user->id,
            'plan' => $plan,
            'currency' => 'BRL',
            'locale' => 'pt-BR',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $workspace->users()->attach($user->id, ['role' => $role]);
        $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        Warehouse::create(['workspace_id' => $workspace->id, 'name' => 'Main Warehouse', 'code' => 'MAIN', 'is_default' => true]);
        Subscription::create(['workspace_id' => $workspace->id, 'plan' => $plan, 'status' => 'active', 'provider' => 'local']);

        return $workspace;
    }
}
