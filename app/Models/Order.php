<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Order extends Model { use HasFactory; protected $fillable=['user_id','customer','total','status']; protected function casts():array{return ['total'=>'decimal:2'];} public function user():BelongsTo{return $this->belongsTo(User::class);} }
