<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Sales\Http\Controllers\Api\V1\SalesOrderController;
use Modules\Sales\Http\Controllers\Api\V1\SalesOrderItemController;
use Illuminate\Support\Facades\Route;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('sales-orders', SalesOrderController::class);
        $server->resource('sales-order-items', SalesOrderItemController::class);
    });

// Custom endpoints for sales reporting
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('sales-orders/reports', [SalesOrderController::class, 'reports'])->name('sales-orders.reports');
    Route::get('sales-orders/customers', [SalesOrderController::class, 'customers'])->name('sales-orders.customers');
});
