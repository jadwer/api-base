<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Purchase\Http\Controllers\Api\V1\PurchaseOrderController;
use Modules\Purchase\Http\Controllers\Api\V1\PurchaseOrderItemController;
use Illuminate\Support\Facades\Route;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('purchase-orders', PurchaseOrderController::class);
        $server->resource('purchase-order-items', PurchaseOrderItemController::class);
    });

// Custom endpoints for purchase reporting
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('purchase-orders/reports', [PurchaseOrderController::class, 'reports'])->name('purchase-orders.reports');
    Route::get('purchase-orders/suppliers', [PurchaseOrderController::class, 'suppliers'])->name('purchase-orders.suppliers');
});
