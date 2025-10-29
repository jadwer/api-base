<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\Api\V1\OrderTrackingController;
use Modules\Sales\Http\Controllers\Api\V1\CustomerOrderController;

/*
|--------------------------------------------------------------------------
| Sales Module API Routes
|--------------------------------------------------------------------------
|
| Order tracking and customer portal routes
|
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Order Tracking Routes (admin + customer)
    Route::prefix('orders')->group(function () {
        Route::get('{order}/tracking', [OrderTrackingController::class, 'tracking']);
        Route::get('{order}/status-history', [OrderTrackingController::class, 'statusHistory']);

        // Admin only
        Route::post('{order}/status', [OrderTrackingController::class, 'updateStatus']);
        Route::post('{order}/ship', [OrderTrackingController::class, 'ship']);
    });

    // Customer Order Portal
    Route::prefix('my-orders')->group(function () {
        Route::get('/', [CustomerOrderController::class, 'index']);
        Route::get('{order}', [CustomerOrderController::class, 'show']);
        Route::post('{order}/cancel', [CustomerOrderController::class, 'cancel']);
        Route::post('{order}/return', [CustomerOrderController::class, 'requestReturn']);
        Route::get('{order}/invoice', [CustomerOrderController::class, 'invoice']);
    });

});
