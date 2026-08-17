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
    public function index(): JsonResponse
    {
        $orders = Order::query()
            ->with('items')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
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

        $order = DB::transaction(function () use ($data) {

            $order = Order::create([
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'total' => 0,
                'status' => 'pending',
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {

                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                $quantity = (int) $item['quantity'];

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Estoque insuficiente para {$product->name}. Disponível: {$product->stock}.",
                        ],
                    ]);
                }

                // O preço vem do banco
                $unitPrice = (float) $product->price;

                // Calcula o subtotal
                $subtotal = round(
                    $unitPrice * $quantity,
                    2
                );

                // Salva o item dentro do pedido
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                // Desconta do estoque
                $product->decrement(
                    'stock',
                    $quantity
                );

                // Soma no total geral
                $total += $subtotal;
            }

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