<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use App\Models\InventoryMovement; use App\Models\Product; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
class InventoryController extends Controller {
 public function index(Request $r):JsonResponse{return response()->json($r->user()->inventoryMovements()->with('product:id,name,sku')->latest()->get());}
 public function store(Request $r):JsonResponse{$v=$r->validate(['product_id'=>['required','integer'],'type'=>['required','in:in,out'],'quantity'=>['required','integer','min:1']]);$u=$r->user();$m=DB::transaction(function()use($u,$v):InventoryMovement{$p=Product::query()->where('user_id',$u->id)->lockForUpdate()->findOrFail($v['product_id']);if($v['type']==='out'&&$v['quantity']>$p->stock){throw ValidationException::withMessages(['quantity'=>['There is not enough stock for this operation.']]);}$p->stock=$v['type']==='in'?$p->stock+$v['quantity']:$p->stock-$v['quantity'];$p->save();return $u->inventoryMovements()->create(['product_id'=>$p->id,'type'=>$v['type'],'quantity'=>$v['quantity']]);});return response()->json($m->load('product:id,name,sku'),201);}
}
