<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $query = $workspace->customers()->withCount('orders');
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%"));
        }
        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'], 'document' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $customer = $workspace->customers()->create($validated);
        $audit->log($request, $workspace, 'customer.created', $customer);
        return response()->json($customer, 201);
    }

    public function update(Request $request, Customer $customer, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($customer->workspace_id === $workspace->id, 404);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'], 'document' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $customer->update($validated);
        $audit->log($request, $workspace, 'customer.updated', $customer);
        return response()->json($customer->fresh());
    }

    public function destroy(Request $request, Customer $customer, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($customer->workspace_id === $workspace->id, 404);
        $audit->log($request, $workspace, 'customer.deleted', $customer, ['name' => $customer->name]);
        $customer->delete();
        return response()->json(['message' => 'Customer deleted.']);
    }
}
