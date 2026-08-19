<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'owner_id', 'plan', 'currency', 'locale', 'timezone',
        'business_type', 'onboarding_completed',
    ];

    protected function casts(): array
    {
        return ['onboarding_completed' => 'boolean'];
    }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps(); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function inventoryMovements(): HasMany { return $this->hasMany(InventoryMovement::class); }
    public function categories(): HasMany { return $this->hasMany(Category::class); }
    public function suppliers(): HasMany { return $this->hasMany(Supplier::class); }
    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function warehouses(): HasMany { return $this->hasMany(Warehouse::class); }
    public function invitations(): HasMany { return $this->hasMany(Invitation::class); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return $this->warehouses()->where('is_default', true)->first()
            ?? $this->warehouses()->first();
    }
}
