<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function parent()
    {
        $items = Category::query()
            ->select(['id', 'name', 'slug', 'parent_id'])
            ->wherenull('parent_id')
            ->get();

        return ApiResponseClass::apiResponse(true, 'OK', [
            'items' => $items
        ], 200);
    }

    public function children($id)
    {
        $parent = Category::query()
            ->select(['id', 'name', 'slug', 'parent_id'])->find($id);
        if(!$parent) {
            return ApiResponseClass::errorResponse('not_found', 'Category not found!', 404);
        }
        $items = Category::query()
            ->select(['id', 'name', 'slug', 'parent_id'])
            ->where('parent_id',$id)
            ->get();

        return ApiResponseClass::apiResponse(true, 'OK', [
            'items' => $items,
            'parent' => $parent,
        ],200);



    }
}
