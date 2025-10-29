<?php

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\Api\V1\CheckoutController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ShippingMethodController;
use Modules\Ecommerce\Http\Controllers\Api\V1\PaymentController;

/*
|--------------------------------------------------------------------------
| Ecommerce API Routes
|--------------------------------------------------------------------------
|
| Checkout and shipping management routes
|
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Checkout Routes
    Route::prefix('checkout')->group(function () {
        Route::post('initiate', [CheckoutController::class, 'initiate']);
        Route::get('{session}', [CheckoutController::class, 'show']);
        Route::put('{session}/address', [CheckoutController::class, 'updateAddress']);
        Route::put('{session}/shipping', [CheckoutController::class, 'selectShipping']);
        Route::get('{session}/summary', [CheckoutController::class, 'summary']);
        Route::delete('{session}', [CheckoutController::class, 'cancel']);
    });

    // Shipping Methods Routes
    Route::prefix('shipping-methods')->group(function () {
        Route::get('/', [ShippingMethodController::class, 'index']);
        Route::get('{id}', [ShippingMethodController::class, 'show']);
        Route::post('{id}/calculate', [ShippingMethodController::class, 'calculate']);
    });

    // Payment Routes
    Route::prefix('payment')->group(function () {
        Route::get('gateways', [PaymentController::class, 'gateways']);
        Route::post('{transaction}/confirm', [PaymentController::class, 'confirm']);
        Route::post('{transaction}/refund', [PaymentController::class, 'refund']);
        Route::post('{transaction}/cancel', [PaymentController::class, 'cancel']);
    });

    Route::post('checkout/{session}/payment', [PaymentController::class, 'process']);
    Route::get('checkout/{session}/payment-status', [PaymentController::class, 'status']);

});

// Webhook Routes (no auth required)
Route::post('v1/webhooks/payment/{gateway}', [PaymentController::class, 'webhook']);

// Las rutas JSON:API del módulo Ecommerce están configuradas en routes/jsonapi.php
