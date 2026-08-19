<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Services\AuditService;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationAcceptController extends Controller
{
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $invitation = Invitation::with('workspace')->where('token', $token)->firstOrFail();
        abort_unless($invitation->isUsable(), 410, 'This invitation has expired or was already accepted.');

        if (! $request->user()) {
            return view('invitation', ['invitation' => $invitation]);
        }
        return view('invitation', ['invitation' => $invitation]);
    }

    public function accept(Request $request, string $token, PlanService $plans, AuditService $audit): RedirectResponse
    {
        $invitation = Invitation::with('workspace')->where('token', $token)->firstOrFail();
        abort_unless($invitation->isUsable(), 410);
        abort_unless(strcasecmp($request->user()->email, $invitation->email) === 0, 403, 'Sign in with the invited email address.');
        abort_unless($plans->canAddMember($invitation->workspace), 422, 'The workspace member limit has been reached.');

        $invitation->workspace->users()->syncWithoutDetaching([$request->user()->id => ['role' => $invitation->role]]);
        $request->user()->update(['current_workspace_id' => $invitation->workspace_id]);
        $invitation->update(['accepted_at' => now()]);
        $audit->log($request, $invitation->workspace, 'invitation.accepted', $invitation, ['email' => $invitation->email]);

        return redirect()->route('app');
    }
}
