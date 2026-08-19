<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request, PlanService $plans): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $days = $plans->limits($workspace)['audit_days'] ?? 7;
        $query = $workspace->auditLogs()->with('actor:id,name,email')->where('created_at', '>=', now()->subDays($days));
        if ($request->filled('action')) $query->where('action', $request->query('action'));
        return response()->json($query->latest()->limit(250)->get());
    }
}
