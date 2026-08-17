<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class DashboardController extends Controller { public function __invoke(Request $request):JsonResponse{ $u=$request->user(); $p=$u->products(); $m=$u->inventoryMovements(); $o=$u->orders(); return response()->json(['total_products'=>(clone $p)->count(),'total_stock'=>(int)(clone $p)->sum('stock'),'low_stock'=>(clone $p)->whereColumn('stock','<=','min_stock')->count(),'total_orders'=>(clone $o)->count(),'recent_movements'=>(clone $m)->with('product:id,name,sku')->latest()->limit(6)->get(),'low_stock_products'=>(clone $p)->whereColumn('stock','<=','min_stock')->orderBy('stock')->limit(8)->get()]); } }
