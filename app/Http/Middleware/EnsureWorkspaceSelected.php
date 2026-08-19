<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            $workspace = $user->workspaces()->first();

            if ($workspace) {
                $user->forceFill(['current_workspace_id' => $workspace->id])->save();
            }
        }

        abort_unless($workspace, 409, 'No workspace is available for this account.');

        $belongsToWorkspace = $user->workspaces()
            ->where('workspaces.id', $workspace->id)
            ->exists();

        abort_unless($belongsToWorkspace, 403);

        $request->attributes->set('workspace', $workspace);

        return $next($request);
    }
}
