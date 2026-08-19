<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'user_id', 'customer_id', 'warehouse_id', 'customer', 'subtotal',
        'discount', 'tax', 'total', 'status', 'notes', 'stock_applied', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'tax' => 'decimal:2',
            'total' => 'decimal:2', 'stock_applied' => 'boolean', 'processed_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function customerRelation(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
}
