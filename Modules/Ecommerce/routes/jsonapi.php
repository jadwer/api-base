<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Ecommerce\Http\Controllers\Api\V1\ShoppingCartController;
use Modules\Ecommerce\Http\Controllers\Api\V1\CartItemController;
use Modules\Ecommerce\Http\Controllers\Api\V1\CouponController;
use Modules\Ecommerce\Http\Controllers\Api\V1\CheckoutSessionController;
use Modules\Ecommerce\Http\Controllers\Api\V1\PaymentTransactionController;
use Modules\Ecommerce\Http\Controllers\Api\V1\InventoryReservationController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ShippingMethodController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ProductReviewController;

// Public routes (authorization handled by Authorizers)
JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function (ResourceRegistrar $server) {
        // Phase 4.3.1 Advanced Ecommerce - Product Reviews (public access for approved reviews)
        $server->resource('product-reviews', ProductReviewController::class);
    });

// Authenticated routes
JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        // Core Ecommerce (Phase 1)
        $server->resource('shopping-carts', ShoppingCartController::class);
        $server->resource('cart-items', CartItemController::class);
        $server->resource('coupons', CouponController::class);

        // Phase 4.1 Enhancement (Rewritten)
        $server->resource('checkout-sessions', CheckoutSessionController::class);
        $server->resource('payment-transactions', PaymentTransactionController::class);
        $server->resource('inventory-reservations', InventoryReservationController::class);
        $server->resource('shipping-methods', ShippingMethodController::class);
    });
