<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->get();

        return response()->json($orders);
    }


    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }

        $order->load('items');

        return response()->json($order);
    }


    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'customer_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);


        $userId = $request->user()->id;


        $order = DB::transaction(function () use ($data, $userId) {

            /*
            |--------------------------------------------------------------------------
            | Criar pedido
            |--------------------------------------------------------------------------
            */

            $order = Order::create([
                'user_id' => $userId,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'total' => 0,
                'status' => 'pending',
            ]);


            $total = 0;


            /*
            |--------------------------------------------------------------------------
            | Adicionar produtos
            |--------------------------------------------------------------------------
            */

            foreach ($data['items'] as $item) {

                /*
                 * Procura somente produtos pertencentes
                 * ao usuário logado.
                 */
                $product = Product::query()
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->find($item['product_id']);


                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Produto não encontrado.',
                        ],
                    ]);
                }


                $quantity = (int) $item['quantity'];


                /*
                |--------------------------------------------------------------------------
                | Conferir estoque
                |--------------------------------------------------------------------------
                */

                if ($product->stock < $quantity) {

                    throw ValidationException::withMessages([
                        'items' => [
                            "Estoque insuficiente para {$product->name}. Disponível: {$product->stock}.",
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Preço vem do banco
                |--------------------------------------------------------------------------
                */

                $unitPrice = (float) $product->price;


                /*
                |--------------------------------------------------------------------------
                | Calcular subtotal
                |--------------------------------------------------------------------------
                */

                $subtotal = round(
                    $unitPrice * $quantity,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Salvar item do pedido
                |--------------------------------------------------------------------------
                */

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Descontar estoque
                |--------------------------------------------------------------------------
                */

                $product->decrement(
                    'stock',
                    $quantity
                );


                /*
                |--------------------------------------------------------------------------
                | Somar ao total
                |--------------------------------------------------------------------------
                */

                $total += $subtotal;
            }


            /*
            |--------------------------------------------------------------------------
            | Atualizar total final
            |--------------------------------------------------------------------------
            */

            $order->update([
                'total' => round($total, 2),
            ]);


            return $order;
        });


        return response()->json(
            $order->load('items'),
            201
        );
    }
}