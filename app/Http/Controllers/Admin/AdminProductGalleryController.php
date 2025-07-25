<?php

namespace App\Http\Controllers\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminProductGalleryController extends Controller
{
    public function index(Product $product)
    {
        return ApiResponseClass::apiResponse(
            'true',
            'Product Gallery retrieved successfully',
            $product->gallery->get(),
            200
        );

    }
    public function store(Product $product, Request $request){

        $request->validate([
            'images'=>'required|array',
            'images.*'=>'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);
        $savedImages = [];

        foreach ($request->file('images') as $image) {
            $path = $image->store('products/gallery', 'public');
            $savedImages[] = $product->gallery()->create([
                'path' => $path,
            ]);
        }

        return ApiResponseClass::apiResponse(
            'true',
            'Images saved successfully',
            $savedImages,
            201
        );
    }

    public function destroy(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return ApiResponseClass::errorResponse('This image does not belong to the product.', null, 403);
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return ApiResponseClass::apiResponse(
            true,
            'Image deleted successfully.',
            null,
            200
        );

    }
}
