<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $query = $workspace->suppliers()->withCount('products');
        if ($request->filled('search')) $query->where('name', 'ilike', '%' . trim((string) $request->query('search')) . '%');
        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $supplier = $workspace->suppliers()->create($validated);
        $audit->log($request, $workspace, 'supplier.created', $supplier);
        return response()->json($supplier, 201);
    }

    public function update(Request $request, Supplier $supplier, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($supplier->workspace_id === $workspace->id, 404);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'], 'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:120'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $supplier->update($validated);
        $audit->log($request, $workspace, 'supplier.updated', $supplier);
        return response()->json($supplier->fresh());
    }

    public function destroy(Request $request, Supplier $supplier, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($supplier->workspace_id === $workspace->id, 404);
        $audit->log($request, $workspace, 'supplier.deleted', $supplier, ['name' => $supplier->name]);
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted.']);
    }
}
