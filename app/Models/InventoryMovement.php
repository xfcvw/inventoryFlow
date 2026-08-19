<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'user_id', 'product_id', 'warehouse_id', 'type', 'quantity',
        'reason', 'reference_type', 'reference_id', 'balance_after',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'balance_after' => 'integer'];
    }

    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
}
