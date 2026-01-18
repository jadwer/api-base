<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Api\V1\ShoppingCartController;
use Modules\Ecommerce\Http\Controllers\Api\V1\CouponController;

/*
|--------------------------------------------------------------------------
| Ecommerce API Routes
|--------------------------------------------------------------------------
|
| Custom non-JSON:API routes for the Ecommerce module.
| Standard CRUD operations are handled via routes/jsonapi.php
|
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Shopping Cart custom endpoints
    Route::post('shopping-carts/get-or-create', [ShoppingCartController::class, 'getOrCreate']);
    Route::get('shopping-carts/current', [ShoppingCartController::class, 'current']);
    Route::post('shopping-carts/merge', [ShoppingCartController::class, 'merge']);
    Route::delete('shopping-carts/{shoppingCart}/clear', [ShoppingCartController::class, 'clear']);
    Route::post('shopping-carts/{shoppingCart}/apply-coupon', [ShoppingCartController::class, 'applyCoupon']);
    Route::post('shopping-carts/{shoppingCart}/remove-coupon', [ShoppingCartController::class, 'removeCoupon']);
    Route::post('shopping-carts/{shoppingCart}/checkout', [ShoppingCartController::class, 'checkout']);

    // Coupon validation endpoint
    Route::get('coupons/validate/{code}', [CouponController::class, 'validate']);
});
