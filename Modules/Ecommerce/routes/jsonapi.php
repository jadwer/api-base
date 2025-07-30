<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Ecommerce\Http\Controllers\Api\V1\ShoppingCartController;
use Modules\Ecommerce\Http\Controllers\Api\V1\CartItemController;
use Modules\Ecommerce\Http\Controllers\Api\V1\CouponController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('shopping-carts', ShoppingCartController::class);
        $server->resource('cart-items', CartItemController::class);
        $server->resource('coupons', CouponController::class);
    });
