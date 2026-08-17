<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use App\Models\Product; use Illuminate\Database\Eloquent\Builder; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request; use Illuminate\Validation\Rule;
class ProductController extends Controller {
 public function index(Request $r):JsonResponse{$q=$r->user()->products();if($r->filled('search')){$s=trim((string)$r->query('search'));$q->where(function(Builder $b)use($s){$b->where('name','ilike',"%{$s}%")->orWhere('sku','ilike',"%{$s}%")->orWhere('category','ilike',"%{$s}%");});}if($r->filled('category'))$q->where('category',$r->query('category'));return response()->json($q->orderBy('name')->get());}
 public function store(Request $r):JsonResponse{$u=$r->user();$v=$r->validate(['name'=>['required','string','max:120'],'sku'=>['required','string','max:60',Rule::unique('products','sku')->where(fn($q)=>$q->where('user_id',$u->id))],'category'=>['required','string','max:80'],'price'=>['required','numeric','min:0'],'stock'=>['required','integer','min:0'],'min_stock'=>['required','integer','min:0']]);return response()->json($u->products()->create($v),201);}
 public function show(Request $r,Product $product):JsonResponse{$this->own($r,$product);return response()->json($product);}
 public function update(Request $r,Product $product):JsonResponse{$this->own($r,$product);$v=$r->validate(['name'=>['required','string','max:120'],'sku'=>['required','string','max:60',Rule::unique('products','sku')->ignore($product->id)->where(fn($q)=>$q->where('user_id',$r->user()->id))],'category'=>['required','string','max:80'],'price'=>['required','numeric','min:0'],'stock'=>['required','integer','min:0'],'min_stock'=>['required','integer','min:0']]);$product->update($v);return response()->json($product->fresh());}
 public function destroy(Request $r,Product $product):JsonResponse{$this->own($r,$product);$product->delete();return response()->json(['message'=>'Product deleted.']);}
 private function own(Request $r,Product $product):void{abort_unless($product->user_id===$r->user()->id,404);}
}
