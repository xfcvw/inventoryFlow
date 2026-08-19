<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Services\AuditService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        return response()->json($workspace->invitations()->whereNull('accepted_at')->latest()->get());
    }

    public function store(Request $request, PlanService $plans, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        if (! $plans->canAddMember($workspace)) throw ValidationException::withMessages(['plan' => ['Member limit reached for this plan.']]);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'role' => ['required', Rule::in(['admin', 'manager', 'member', 'viewer'])],
        ]);
        abort_if($workspace->users()->where('users.email', $validated['email'])->exists(), 422, 'This email is already a workspace member.');

        $invitation = $workspace->invitations()->updateOrCreate(
            ['email' => strtolower($validated['email']), 'accepted_at' => null],
            ['invited_by' => $request->user()->id, 'role' => $validated['role'], 'token' => Str::random(64), 'expires_at' => now()->addDays(7)]
        );
        Notification::route('mail', $invitation->email)->notify(new WorkspaceInvitationNotification($invitation));
        $audit->log($request, $workspace, 'invitation.sent', $invitation, ['email' => $invitation->email, 'role' => $invitation->role]);
        return response()->json(['invitation' => $invitation, 'accept_url' => route('invitation.show', $invitation->token)], 201);
    }

    public function destroy(Request $request, Invitation $invitation, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $audit->log($request, $workspace, 'invitation.cancelled', $invitation, ['email' => $invitation->email]);
        $invitation->delete();
        return response()->json(['message' => 'Invitation cancelled.']);
    }
}
