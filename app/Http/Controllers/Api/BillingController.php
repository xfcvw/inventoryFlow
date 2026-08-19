<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\AuditService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function show(Request $request, PlanService $plans): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        return response()->json([
            'current_plan' => $workspace->plan,
            'plans' => collect(config('plans'))->map(fn ($limits, $key) => ['key' => $key, ...$limits])->values(),
            'usage' => ['products' => $workspace->products()->count(), 'members' => $workspace->users()->count(), 'warehouses' => $workspace->warehouses()->count()],
            'subscription' => $workspace->activeSubscription,
            'mode' => 'local_simulator',
        ]);
    }

    public function change(Request $request, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $validated = $request->validate(['plan' => ['required', Rule::in(array_keys(config('plans')))] ]);

        DB::transaction(function () use ($workspace, $validated) {
            $workspace->subscriptions()->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            Subscription::create([
                'workspace_id' => $workspace->id, 'plan' => $validated['plan'], 'status' => 'active',
                'provider' => 'local', 'current_period_ends_at' => now()->addMonth(),
            ]);
            $workspace->update(['plan' => $validated['plan']]);
        });
        $audit->log($request, $workspace, 'billing.plan_changed', $workspace, ['plan' => $validated['plan'], 'mode' => 'local_simulator']);
        return response()->json(['message' => 'Plan changed in local billing simulator.', 'plan' => $workspace->fresh()->plan]);
    }
}
