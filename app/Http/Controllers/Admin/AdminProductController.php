<?php

namespace App\Http\Controllers\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::query()->with('category')->latest()->get();
        return ApiResponseClass::apiResponse(
            'true',
            'Products list retrieved  successfully',
            $products,
            200

        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|unique:products,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return ApiResponseClass::errorResponse(
                'Validation Error',
                $validator->errors(),
                422
            );
        }

        $date = $request->only(['category_id', 'name', 'description', 'price', 'stock']);
        $date['slug'] = Str::slug($date['name'], '-');

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('products', 'public');
            $date['thumbnail'] = $path;
        }
        $product = Product::query()->create($date);

        return ApiResponseClass::apiResponse(
            'true',
            'Product created successfully',
            $product,
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::query()->findOrFail($id);
        return ApiResponseClass::apiResponse(
            'true',
            'Product retrieved  successfully',
            $product,
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $product = Product::query()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|unique:products,name,'.$id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return ApiResponseClass::errorResponse(
                'Validation Error',
                $validator->errors(),
                422
            );
        }
        $date = $request->only(['category_id', 'name', 'description', 'price', 'stock']);
        $date['slug'] = Str::slug($date['name'], '-');

        if ($request->hasFile('thumbnail')) {

            if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail)) {
                Storage::disk('public')->delete($product->thumbnail);
            }

            $path = $request->file('thumbnail')->store('products', 'public');
            $date['thumbnail'] = $path;
        }
        $product->update($date);

        return ApiResponseClass::apiResponse(
            'true',
            'Product updated successfully',
            $product,
            201
        );


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::query()->findOrFail($id);
        $product->delete();
        Storage::disk('public')->delete($product->thumbnail);
    }
}
