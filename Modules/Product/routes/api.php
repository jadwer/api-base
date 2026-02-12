<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\V1\ProductUploadController;
use Modules\Product\Http\Controllers\Api\V1\ProductImageCustomController;

/*
|--------------------------------------------------------------------------
| Product Upload Routes (Custom - Non JSON:API)
|--------------------------------------------------------------------------
| These routes handle file uploads for product images and datasheets.
| They use standard Laravel file handling since JSON:API doesn't support
| multipart/form-data natively.
|
| Base path: /api (from RouteServiceProvider)
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('products/upload-image', [ProductUploadController::class, 'uploadImage'])
        ->name('products.upload-image');
    Route::post('products/upload-datasheet', [ProductUploadController::class, 'uploadDatasheet'])
        ->name('products.upload-datasheet');

    // Product Images - Custom endpoints
    Route::post('product-images/reorder', [ProductImageCustomController::class, 'reorder'])
        ->name('product-images.reorder');
    Route::post('product-images/{productImage}/set-primary', [ProductImageCustomController::class, 'setPrimary'])
        ->name('product-images.set-primary');
});

/*
|--------------------------------------------------------------------------
| Public Download Routes (No Auth Required)
|--------------------------------------------------------------------------
| Datasheet downloads are public but tracked for analytics.
| Logs: IP, User-Agent, user (if authenticated), timestamp, product
*/
Route::prefix('v1')->group(function () {
    Route::get('products/{product}/datasheet', [ProductUploadController::class, 'downloadDatasheet'])
        ->name('products.download-datasheet');
});
