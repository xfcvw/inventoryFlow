<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use App\Models\Order; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class OrderController extends Controller {
 public function index(Request $r):JsonResponse{$q=$r->user()->orders();if($r->filled('search'))$q->where('customer','ilike','%'.trim((string)$r->query('search')).'%');if($r->filled('status'))$q->where('status',$r->query('status'));return response()->json($q->latest()->get());}
 public function store(Request $r):JsonResponse{$v=$r->validate(['customer'=>['required','string','max:120'],'total'=>['required','numeric','min:0'],'status'=>['required','in:pending,processing,completed,cancelled']]);return response()->json($r->user()->orders()->create($v),201);}
 public function show(Request $r,Order $order):JsonResponse{$this->own($r,$order);return response()->json($order);}
 public function update(Request $r,Order $order):JsonResponse{$this->own($r,$order);$v=$r->validate(['customer'=>['sometimes','required','string','max:120'],'total'=>['sometimes','required','numeric','min:0'],'status'=>['sometimes','required','in:pending,processing,completed,cancelled']]);$order->update($v);return response()->json($order->fresh());}
 public function destroy(Request $r,Order $order):JsonResponse{$this->own($r,$order);$order->delete();return response()->json(['message'=>'Order deleted.']);}
 private function own(Request $r,Order $order):void{abort_unless($order->user_id===$r->user()->id,404);}
}
