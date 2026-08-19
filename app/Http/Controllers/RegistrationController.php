<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(Request $request): View
    {
        $invitation = $request->filled('invite')
            ? Invitation::with('workspace')->where('token', $request->query('invite'))->whereNull('accepted_at')->first()
            : null;
        return view('register', ['invitation' => $invitation]);
    }

    public function store(Request $request): RedirectResponse
    {
        $invitation = $request->filled('invite_token')
            ? Invitation::with('workspace')->where('token', $request->input('invite_token'))->first()
            : null;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'workspace_name' => [$invitation ? 'nullable' : 'required', 'nullable', 'string', 'max:120'],
            'invite_token' => ['nullable', 'string'],
        ]);

        if ($invitation) {
            abort_unless($invitation->isUsable(), 410);
            abort_unless(strcasecmp($validated['email'], $invitation->email) === 0, 422, 'Use the same email address that received the invitation.');
        }

        $user = DB::transaction(function () use ($validated, $invitation): User {
            $user = User::create(['name' => $validated['name'], 'email' => $validated['email'], 'password' => $validated['password']]);

            if ($invitation) {
                $invitation->workspace->users()->attach($user->id, ['role' => $invitation->role]);
                $user->update(['current_workspace_id' => $invitation->workspace_id]);
                $invitation->update(['accepted_at' => now()]);
                return $user;
            }

            $workspaceName = $validated['workspace_name'];
            $baseSlug = Str::slug($workspaceName) ?: 'workspace'; $slug = $baseSlug; $suffix = 2;
            while (Workspace::where('slug', $slug)->exists()) $slug = $baseSlug . '-' . $suffix++;

            $workspace = Workspace::create([
                'name' => $workspaceName, 'slug' => $slug, 'owner_id' => $user->id, 'plan' => 'free',
                'currency' => 'BRL', 'locale' => 'pt-BR', 'timezone' => 'America/Sao_Paulo',
            ]);
            $workspace->users()->attach($user->id, ['role' => 'owner']);
            Warehouse::create(['workspace_id' => $workspace->id, 'name' => 'Main Warehouse', 'code' => 'MAIN', 'is_default' => true]);
            Subscription::create(['workspace_id' => $workspace->id, 'plan' => 'free', 'status' => 'active', 'provider' => 'local']);
            $user->update(['current_workspace_id' => $workspace->id]);
            return $user;
        });

        Auth::login($user); $request->session()->regenerate();
        return redirect()->route('app');
    }
}
