<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Inventory\Http\Controllers\Api\V1\WarehouseController;
use Modules\Inventory\Http\Controllers\Api\V1\WarehouseLocationController;
use Modules\Inventory\Http\Controllers\Api\V1\ProductBatchController;
use Modules\Inventory\Http\Controllers\Api\V1\StockController;
use Modules\Inventory\Http\Controllers\Api\V1\InventoryMovementController;
use Modules\Inventory\Http\Controllers\Api\V1\CycleCountController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('warehouses', WarehouseController::class);
        $server->resource('warehouse-locations', WarehouseLocationController::class);
        $server->resource('product-batches', ProductBatchController::class);
        $server->resource('stocks', StockController::class);
        $server->resource('inventory-movements', InventoryMovementController::class);
        $server->resource('cycle-counts', CycleCountController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('warehouse');
                $relationships->hasOne('warehouseLocation');
                $relationships->hasOne('product');
            });
    });

