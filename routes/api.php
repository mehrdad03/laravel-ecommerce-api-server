<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;


Route::prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('/Payments', AdminPaymentController::class);

    Route::apiResource('/categories', AdminCategoryController::class);
    Route::apiResource('/products', AdminProductController::class);
    Route::apiResource('/orders', AdminOrderController::class);
    Route::apiResource('/users', AdminUserController::class);
    Route::apiResource('/users', AdminUserController::class);

});
