<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        $query = Product::query()
            ->select('id', 'name', 'slug', 'price', 'stock', 'category_id', 'thumbnail')
            ->with(['category:id,name']);


        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->get('q') . '%');
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->get('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->get('max_price'));
        }


        switch ($request->get('sort')) {
            case 'price-asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price-desc':
                $query->orderBy('price', 'desc');
                break;
            case 'latest':
            default:
                $query->orderBy('id', 'desc');
                break;
        }


        $per_page = (int)$request->get('per_page', 10);
        if ($per_page < 1) $per_page = 10;
        if ($per_page > 100) $per_page = 100;

        $page = $query->paginate($per_page);

        $items = collect($page->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'price' => $item->price,
                'stock' => $item->stcok,
                'thumbnail' => $item->thumbnail,
                'category' => [
                    'id' => $item->category?->id,
                    'name' => $item->category?->name,
                ]
            ];
        });

        return ApiResponseClass::apiResponse(true, 'ok', [
            'items' => $items,
            'meta' => [
                'total' => $page->total(),
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'last_page' => $page->lastPage(),
            ]

        ], 200);
    }

    public function show(Request $request, $id)
    {
        $product = Product::query()
            ->with([
                'category:id,name',
                'gallery:id,product_id,path',
            ])
            ->findOrFail($id);
        if (!$product) return ApiResponseClass::errorResponse('not_found', 'Product not found', 404);

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'description' => $product->description,
            'stock' => $product->stcok,
            'thumbnail' => $product->thumbnail,
            'category' => [
                'id' => $product->category?->id,
                'name' => $product->category?->name,
            ],
            'images' => $product->gallery?->map(fn($img) => $img->path)->values()
        ];

        return ApiResponseClass::apiResponse(true, 'ok', $data, 200);
    }


}
