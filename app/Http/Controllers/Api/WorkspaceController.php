<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\AuditService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    public function index(Request $request, PlanService $plans): JsonResponse
    {
        $user = $request->user();
        $workspaces = $user->workspaces()->orderBy('name')->get()->map(fn (Workspace $workspace) => [
            'id' => $workspace->id, 'name' => $workspace->name, 'slug' => $workspace->slug,
            'plan' => $workspace->plan, 'role' => $user->roleInWorkspace($workspace), 'limits' => $plans->limits($workspace),
        ]);
        return response()->json(['current_workspace_id' => $user->current_workspace_id, 'workspaces' => $workspaces]);
    }

    public function switch(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->workspaces()->where('workspaces.id', $workspace->id)->exists(), 404);
        $user->update(['current_workspace_id' => $workspace->id]);
        return response()->json(['message' => 'Workspace changed.', 'workspace_id' => $workspace->id]);
    }

    public function show(Request $request, PlanService $plans): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        return response()->json([
            'id' => $workspace->id, 'name' => $workspace->name, 'slug' => $workspace->slug, 'plan' => $workspace->plan,
            'currency' => $workspace->currency, 'locale' => $workspace->locale, 'timezone' => $workspace->timezone,
            'business_type' => $workspace->business_type, 'onboarding_completed' => $workspace->onboarding_completed,
            'role' => $request->user()->roleInWorkspace($workspace), 'limits' => $plans->limits($workspace),
            'usage' => ['products' => $workspace->products()->count(), 'members' => $workspace->users()->count(), 'warehouses' => $workspace->warehouses()->count()],
        ]);
    }

    public function update(Request $request, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'currency' => ['required', Rule::in(['BRL', 'USD', 'EUR'])],
            'locale' => ['required', Rule::in(['pt-BR', 'en-US'])], 'timezone' => ['required', 'string', 'max:60'],
            'business_type' => ['nullable', 'string', 'max:80'], 'onboarding_completed' => ['boolean'],
        ]);
        $workspace->update([...$validated, 'slug' => $this->uniqueSlug($validated['name'], $workspace)]);
        $audit->log($request, $workspace, 'workspace.updated', $workspace);
        return response()->json($workspace->fresh());
    }

    private function uniqueSlug(string $name, Workspace $workspace): string
    {
        $base = Str::slug($name) ?: 'workspace'; $slug = $base; $suffix = 2;
        while (Workspace::where('slug', $slug)->where('id', '!=', $workspace->id)->exists()) $slug = $base . '-' . $suffix++;
        return $slug;
    }
}
