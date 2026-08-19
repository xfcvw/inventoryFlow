<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        return response()->json($workspace->users()->select('users.id', 'users.name', 'users.email')->orderBy('users.name')->get()->map(fn ($user) => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'role' => $workspace->owner_id === $user->id ? 'owner' : $user->pivot->role,
        ]));
    }

    public function update(Request $request, User $user, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_if($workspace->owner_id === $user->id, 422, 'The owner role cannot be changed here.');
        abort_unless($workspace->users()->where('users.id', $user->id)->exists(), 404);
        $validated = $request->validate(['role' => ['required', Rule::in(['admin', 'manager', 'member', 'viewer'])]]);
        $workspace->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);
        $audit->log($request, $workspace, 'team.role_updated', $user, ['role' => $validated['role']]);
        return response()->json(['message' => 'Role updated.']);
    }

    public function destroy(Request $request, User $user, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_if($workspace->owner_id === $user->id, 422, 'The owner cannot be removed.');
        abort_unless($workspace->users()->where('users.id', $user->id)->exists(), 404);
        $workspace->users()->detach($user->id);
        if ($user->current_workspace_id === $workspace->id) $user->update(['current_workspace_id' => $user->workspaces()->value('workspaces.id')]);
        $audit->log($request, $workspace, 'team.member_removed', $user);
        return response()->json(['message' => 'Member removed.']);
    }
}
