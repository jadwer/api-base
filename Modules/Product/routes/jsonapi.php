<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Product\Http\Controllers\Api\V1\ProductController;
use Modules\Product\Http\Controllers\Api\V1\UnitController;
use Modules\Product\Http\Controllers\Api\V1\CategoryController;
use Modules\Product\Http\Controllers\Api\V1\BrandController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('products', ProductController::class);
        $server->resource('units', UnitController::class);
        $server->resource('categories', CategoryController::class);
        $server->resource('brands', BrandController::class);
    });
