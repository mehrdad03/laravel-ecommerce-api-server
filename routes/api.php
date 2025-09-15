<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('/Payments', AdminPaymentController::class);

    Route::apiResource('/categories', AdminCategoryController::class);
    Route::apiResource('/products', AdminProductController::class);
    Route::apiResource('/orders', AdminOrderController::class);
    Route::apiResource('/users', AdminUserController::class);
    Route::apiResource('/users', AdminUserController::class);

    Route::prefix('product/{product}/gallery')->controller(\App\Http\Controllers\Admin\AdminProductGalleryController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::delete('{image}', 'destroy');
    });

    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me', [AdminAuthController::class, 'me']);

});

Route::post('/admin/login', [AdminAuthController::class, 'login']);



/*************** Users Api ******************/

Route::prefix('v1')->group(function () {

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    Route::get('/categories', [CategoryController::class, 'parent']);
    Route::get('/categories/{id}/children', [CategoryController::class, 'children']);


});
