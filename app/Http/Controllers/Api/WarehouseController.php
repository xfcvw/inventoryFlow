<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        return response()->json($workspace->warehouses()->withCount('stocks')->orderByDesc('is_default')->orderBy('name')->get());
    }

    public function store(Request $request, PlanService $plans, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        if (! $plans->canCreateWarehouse($workspace)) throw ValidationException::withMessages(['plan' => ['Warehouse limit reached for this plan.']]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('warehouses', 'code')->where(fn ($q) => $q->where('workspace_id', $workspace->id))],
            'is_default' => ['boolean'],
        ]);
        $warehouse = DB::transaction(function () use ($workspace, $validated) {
            if ($validated['is_default'] ?? false) $workspace->warehouses()->update(['is_default' => false]);
            $warehouse = $workspace->warehouses()->create($validated);
            if ($workspace->warehouses()->count() === 1 && ! $warehouse->is_default) $warehouse->update(['is_default' => true]);
            return $warehouse;
        });
        $audit->log($request, $workspace, 'warehouse.created', $warehouse);
        return response()->json($warehouse, 201);
    }

    public function update(Request $request, Warehouse $warehouse, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($warehouse->workspace_id === $workspace->id, 404);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('warehouses', 'code')->ignore($warehouse->id)->where(fn ($q) => $q->where('workspace_id', $workspace->id))],
            'is_default' => ['boolean'],
        ]);
        DB::transaction(function () use ($workspace, $warehouse, $validated) {
            if ($validated['is_default'] ?? false) $workspace->warehouses()->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
            $warehouse->update($validated);
        });
        $audit->log($request, $workspace, 'warehouse.updated', $warehouse);
        return response()->json($warehouse->fresh());
    }

    public function destroy(Request $request, Warehouse $warehouse, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($warehouse->workspace_id === $workspace->id, 404);
        abort_if($warehouse->is_default, 422, 'The default warehouse cannot be deleted.');
        abort_if($warehouse->stocks()->where('quantity', '!=', 0)->exists(), 422, 'Move the remaining stock before deleting this warehouse.');
        $audit->log($request, $workspace, 'warehouse.deleted', $warehouse, ['name' => $warehouse->name]);
        $warehouse->delete();
        return response()->json(['message' => 'Warehouse deleted.']);
    }
}
