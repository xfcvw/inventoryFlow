<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'user_id', 'category_id', 'supplier_id', 'name', 'sku', 'barcode',
        'category', 'description', 'price', 'cost_price', 'stock', 'min_stock', 'active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function categoryRelation(): BelongsTo { return $this->belongsTo(Category::class, 'category_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function movements(): HasMany { return $this->hasMany(InventoryMovement::class); }
    public function warehouseStocks(): HasMany { return $this->hasMany(ProductWarehouseStock::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
}
