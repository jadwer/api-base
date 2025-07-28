<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Sales\Http\Controllers\Api\V1\CustomerController;
use Modules\Sales\Http\Controllers\Api\V1\SalesOrderController;
use Modules\Sales\Http\Controllers\Api\V1\SalesOrderItemController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('customers', CustomerController::class);
        $server->resource('sales-orders', SalesOrderController::class);
        $server->resource('sales-order-items', SalesOrderItemController::class);
    });
