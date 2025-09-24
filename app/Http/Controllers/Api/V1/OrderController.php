<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $orders = Order::query()
            ->select('id', 'status', 'price', 'created_at')
            ->where('user_id', $userId)
            ->paginate(10);

        $items = collect($orders->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'status' => $item->status,
                'price' => $item->price,
                'created_at' => $item->created_at,
            ];
        });

        return ApiResponseClass::apiResponse('true', 'OK', [
            'items' => $items,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),

            ]
        ], 200);

    }

    public function show($id, Request $request)
    {
        $userId = $request->user()->id;
        $order = Order::query()
            ->select('id', 'status', 'price', 'user_id', 'created_at')
            ->with(['items' => function ($query) {
                $query->select('id', 'order_id','product_id', 'price', 'quantity')
                    ->with(['product:id,name,thumbnail']);
            }])
            ->where('user_id', $userId)
            ->find($id);
        if (!$order) {
            return ApiResponseClass::errorResponse('not_found', 'Order not found', 404);
        }
        $payLoad=[
            'id' => $order->id,
            'status' => $order->status,
            'price' => $order->price,
            'created_at' => $order->created_at,
            'items'=>$order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'thumbnail' => $item->product?->thumbnail,
                    'name' => $item->product?->name,
                    'line_total' => $item->price*$item->quantity,

                ];
            })->values(),
        ];

        return ApiResponseClass::apiResponse('true', 'OK', $payLoad,200);



    }

    public function store(Request $request)
    {

        $user = $request->user();
        $userId = $user->id;

        $cartItems = CartItem::query()
            ->with('product:id,name,price,stock')
            ->where('user_id', $userId)
            ->get(['id', 'product_id', 'quantity']);

        if ($cartItems->isEmpty()) {
            return ApiResponseClass::errorResponse('empty_cart', 'Your cart is empty', 422);
        }

        $orderTotal = 0;
        $lines = [];
        foreach ($cartItems as $item) {

            $product = $item->product;
            if (!$product) {
                return ApiResponseClass::errorResponse('product_not_found', 'Product not found', 422);
            }

            if ($item->quantity > $product->stock) {
                return ApiResponseClass::errorResponse('product_not_available', 'Product not available', 422);
            }

            $unitPrice = $product->price;
            $lineTotal = $item->quantity * $unitPrice;
            $orderTotal += $lineTotal;

            $lines[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
            ];

            DB::beginTransaction();
            try {
                $order = Order::query()->create([
                    'user_id' => $userId,
                    'status' => 'pending',
                    'price' => $orderTotal,
                ]);
                foreach ($lines as $line) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['unit_price'],
                    ]);
                }


                CartItem::query()->where('user_id', $userId)->delete();
                DB::commit();

                $payLoad = [
                    'id' => $order->id,
                    'status' => $order->status,
                    'price' => $order->price,
                    'items' => array_map(function ($item) {
                        return [
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'price' => $item['unit_price'],
                            'line_total' => $item['quantity'] * $item['unit_price'],
                        ];
                    }, $lines),
                ];

                //TODO:Payment,reduce stock of product

                return ApiResponseClass::apiResponse(true, 'Order created (pending)', $payLoad, 200);

            } catch (\Exception $exception) {
                DB::rollBack();
                return ApiResponseClass::errorResponse('server_error', $exception->getMessage(), 422);
            }

        }
    }
}
