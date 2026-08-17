<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InventoryMovement extends Model { use HasFactory; protected $fillable=['user_id','product_id','type','quantity']; protected function casts():array{return ['quantity'=>'integer'];} public function user():BelongsTo{return $this->belongsTo(User::class);} public function product():BelongsTo{return $this->belongsTo(Product::class);} }
