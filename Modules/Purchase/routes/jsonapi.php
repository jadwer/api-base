<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Purchase\Http\Controllers\Api\V1\SupplierController;
use Modules\Purchase\Http\Controllers\Api\V1\PurchaseOrderController;
use Modules\Purchase\Http\Controllers\Api\V1\PurchaseOrderItemController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('suppliers', SupplierController::class);
        $server->resource('purchase-orders', PurchaseOrderController::class);
        $server->resource('purchase-order-items', PurchaseOrderItemController::class);
    });
