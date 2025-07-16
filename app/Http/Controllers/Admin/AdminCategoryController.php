<?php

namespace App\Http\Controllers\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::query()->latest()->get();
        return ApiResponseClass::apiResponse(
            'true',
            'Category list retrieved successfully',
            $categories,
            200
        );

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:categories,name',
        ]);

        if ($validator->fails()) {
            return ApiResponseClass::errorResponse(
                'Validation Error',
                $validator->errors(),
                422
            );
        }
        $category = Category::query()->create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return ApiResponseClass::apiResponse(
            'true',
            'Category created successfully',
            $category,
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::query()->findOrFail($id);


        return ApiResponseClass::apiResponse(
            'true',
            'Category retrieved successfully',
            $category,
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $category = Category::query()->findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:categories,name,' . $category->id,
        ]);

        if ($validator->fails()) {
            return ApiResponseClass::errorResponse(
                'Validation Error',
                $validator->errors(),
                422
            );
        }

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return ApiResponseClass::apiResponse(
            'true',
            'Category updated successfully',
            $category,
            200
        );

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::query()->findOrFail($id);
        $category->delete();
        return ApiResponseClass::apiResponse(
            'true',
            'Category deleted successfully',
            $category,
            200
        );
    }
}
