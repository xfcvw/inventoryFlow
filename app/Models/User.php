<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'current_workspace_id'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)->withPivot('role')->withTimestamps();
    }

    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function inventoryMovements(): HasMany { return $this->hasMany(InventoryMovement::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }

    public function roleInWorkspace(Workspace $workspace): ?string
    {
        if ($workspace->owner_id === $this->id) return 'owner';

        $membership = $this->workspaces()->where('workspaces.id', $workspace->id)->first();
        return $membership?->pivot?->role;
    }

    public function currentWorkspaceRole(): ?string
    {
        $workspace = $this->currentWorkspace;
        return $workspace ? $this->roleInWorkspace($workspace) : null;
    }
}
