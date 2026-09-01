<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Api\V1\ShoppingCartController;
use Modules\Ecommerce\Http\Controllers\Api\V1\CouponController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ProductViewController;

/*
|--------------------------------------------------------------------------
| Ecommerce API Routes
|--------------------------------------------------------------------------
|
| Custom non-JSON:API routes for the Ecommerce module.
| Standard CRUD operations are handled via routes/jsonapi.php
|
*/

// Public routes - Guest cart operations with session_id
// Authorization is handled in controller via authorizeCartAccess()
Route::prefix('v1')->group(function () {
    Route::post('shopping-carts/get-or-create', [ShoppingCartController::class, 'getOrCreate']);
    Route::get('shopping-carts/current', [ShoppingCartController::class, 'current']);
    Route::delete('shopping-carts/{shoppingCart}/clear', [ShoppingCartController::class, 'clear']);
    Route::post('shopping-carts/{shoppingCart}/apply-coupon', [ShoppingCartController::class, 'applyCoupon']);
    Route::post('shopping-carts/{shoppingCart}/remove-coupon', [ShoppingCartController::class, 'removeCoupon']);

    // Product view tracking (guests and authenticated users)
    Route::post('products/{id}/track-view', [ProductViewController::class, 'trackView'])
        ->whereNumber('id');
});

// Authenticated routes - operations that require user identity
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Merge requires auth to know which user to merge to
    Route::post('shopping-carts/merge', [ShoppingCartController::class, 'merge']);
    // Checkout requires auth to create order with contact
    Route::post('shopping-carts/{shoppingCart}/checkout', [ShoppingCartController::class, 'checkout']);

    // Coupon validation endpoint (rate limited to prevent brute-force)
    Route::get('coupons/validate/{code}', [CouponController::class, 'validate'])
        ->middleware('throttle:10,1');

    // Recently viewed products (requires auth to identify user)
    Route::get('products/recently-viewed', [ProductViewController::class, 'recentlyViewed']);
});
