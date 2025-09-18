<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $items = CartItem::query()
            ->with('product:id,name,price,thumbnail,stock')
            ->where('user_id', $userId)
            ->get(['id', 'product_id', 'quantity']);

        $totalQty = 0;
        $totalPrice = 0;

        $payloadItems = $items->map(function ($item) use (&$totalQty, &$totalPrice) {

            $p = $item->product;
            $lineTotal = $item->quantity * $p->price;

            $totalQty += $item->quantity;

            $totalPrice += $lineTotal;

            return [
                'id' => $item->id,
                'product' => $p ? [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'thumbnail' => $p->thumbnail,
                    'stock' => $p->stock,

                ] : null,
                'quantity' => $item->quantity,
                'line_total' => $lineTotal,
            ];

        });
        return ApiResponseClass::apiResponse(true, 'ok', [
            'items' => $payloadItems,
            'totalQty' => $totalQty,
            'total_price' => $totalPrice,
        ], 200);

    }

    public function store(Request $request)
    {

        $v = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'product_id' => 'required|integer|min:1|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        if ($v->fails()) {
            return ApiResponseClass::errorResponse(false, [
                'errors' => $v->errors()
            ], 422);
        }

        $userId = Auth::id();
        $productId = $request->product_id;
        $qty = $request->quantity ?? 1;

        $product = Product::query()->select('id', 'stock')->find($productId);
        if (!$product) {
            return ApiResponseClass::errorResponse('not_found', 'Product not found', 404);
        }
        if ($qty < 1) $qty = 1;
        if ($qty > 100) $qty = 100;

        DB::beginTransaction();

        try {

            $item = CartItem::query()
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();
            if ($item) {
                $newQty = $item->quantity + $qty;
                if ($newQty > $product->stock) {
                    DB::rollBack();
                    return ApiResponseClass::errorResponse('out_of_stock', 'Product out of stock', 422);
                }
                $item->quantity = $newQty;
                $item->save();
            } else {
                if ($qty > $product->stock) {
                    DB::rollBack();
                    return ApiResponseClass::errorResponse('out_of_stock', 'Product out of stock', 422);
                }
                $item = CartItem::query()->create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                ]);

            }
            DB::commit();

            return ApiResponseClass::apiResponse(true, 'Add to cart', [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'product_id' => $item->product_id,
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();
            return ApiResponseClass::errorResponse('server_error', $e->getMessage(), 500);
        }


    }

    public function update($itemId, Request $request)
    {

        $v = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1|max:100',
        ]);
        if ($v->fails()) {
            return ApiResponseClass::errorResponse(false, [
                'errors' => $v->errors()
            ], 422);
        }
        $userId = Auth::id();
        $item = CartItem::query()
            ->where('id', $itemId)
            ->where('user_id', $userId)
            ->first();


        if (!$item) {
            return ApiResponseClass::errorResponse('not_found', 'Cart item not found', 404);
        }
        $product = Product::query()
            ->select('id', 'stock')
            ->find($item->product_id);
        if (!$product) {
            return ApiResponseClass::errorResponse('not_found', 'Product not found', 404);
        }

        $newQty = $request->quantity;
        if ($newQty > $product->stock) {
            return ApiResponseClass::errorResponse('out_of_stock', 'Product out of stock', 422);
        }
        $item->quantity = $newQty;
        $item->save();

        return ApiResponseClass::apiResponse(true, 'Cart item updated', [
            'id' => $item->id,
            'quantity' => $item->quantity,
            'product_id' => $item->product_id,
        ], 200);


    }

    public function destroy($itemId)
    {
        $userId = Auth::id();
        $item = CartItem::query()
            ->where('product_id', $itemId)
            ->where('user_id', $userId)
            ->first();


        if (!$item) {
            return ApiResponseClass::errorResponse('not_found', 'Product not found', 404);
        }
        $item->delete();
        return ApiResponseClass::apiResponse(true, 'Cart item removed', null, 200);

    }

    public function clear()
    {
        $userId = Auth::id();
        CartItem::query()->where('user_id', $userId)->delete();
        return ApiResponseClass::apiResponse(true, 'Cart cleared', null, 200);


    }
}
