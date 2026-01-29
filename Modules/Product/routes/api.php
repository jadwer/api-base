<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\V1\ProductUploadController;

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
});
