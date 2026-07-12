<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\V1\ProductUploadController;
use Modules\Product\Http\Controllers\Api\V1\ProductImageCustomController;
use Modules\Product\Http\Controllers\Api\V1\ProductBulkController;

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

    // Bulk operations
    Route::post('products/bulk-toggle-active', [ProductBulkController::class, 'bulkToggleActive'])
        ->name('products.bulk-toggle-active');
    Route::post('products/bulk-toggle-by-brand', [ProductBulkController::class, 'bulkToggleByBrand'])
        ->name('products.bulk-toggle-by-brand');
    Route::post('products/bulk-toggle-by-category', [ProductBulkController::class, 'bulkToggleByCategory'])
        ->name('products.bulk-toggle-by-category');
    Route::post('products/bulk-price-update', [ProductBulkController::class, 'bulkPriceUpdate'])
        ->name('products.bulk-price-update');
    Route::post('products/bulk-assign-category-by-brand', [ProductBulkController::class, 'bulkAssignCategoryByBrand'])
        ->name('products.bulk-assign-category-by-brand');
    Route::post('brands/{brand}/toggle-active', [ProductBulkController::class, 'toggleBrandActive'])
        ->name('brands.toggle-active');
    Route::post('categories/{category}/toggle-active', [ProductBulkController::class, 'toggleCategoryActive'])
        ->name('categories.toggle-active');
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
