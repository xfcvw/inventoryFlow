<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var Workspace|null $workspace */
        $workspace = $request->attributes->get('workspace') ?? $request->user()?->currentWorkspace;

        abort_unless($workspace, 409, 'No workspace selected.');

        $role = $request->user()->roleInWorkspace($workspace);

        abort_unless(in_array($role, $roles, true), 403, 'You do not have permission to perform this action.');

        return $next($request);
    }
}
