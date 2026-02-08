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
use Modules\Ecommerce\Http\Controllers\Api\V1\WishlistController;
use Modules\Ecommerce\Http\Controllers\Api\V1\WishlistItemController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ProductRecommendationController;
use Modules\Ecommerce\Http\Controllers\Api\V1\CurrencyController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ProductComparisonController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ProductComparisonItemController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ProductQuestionController;
use Modules\Ecommerce\Http\Controllers\Api\V1\ProductAnswerController;
use Illuminate\Support\Facades\Route;

// Public routes (authorization handled by Authorizers)
JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function (ResourceRegistrar $server) {
        // Phase 4.3.4 Advanced Ecommerce - Currencies (public read, admin write)
        $server->resource('currencies', CurrencyController::class);

        // Phase 4.3.1 Advanced Ecommerce - Product Reviews (public read, auth write)
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

        // Phase 4.3.2 Advanced Ecommerce - Wishlist System
        $server->resource('wishlists', WishlistController::class);
        $server->resource('wishlist-items', WishlistItemController::class);

        // Phase 4.3.5 Advanced Ecommerce - Product Comparison System
        $server->resource('product-comparisons', ProductComparisonController::class);
        $server->resource('product-comparison-items', ProductComparisonItemController::class);

        // Phase 4.3.6 Advanced Ecommerce - Customer Q&A System
        $server->resource('product-questions', ProductQuestionController::class);
        $server->resource('product-answers', ProductAnswerController::class);
    });

// Phase 4.3.3 Advanced Ecommerce - Product Recommendations
// Public routes for recommendations
Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('products/{id}/related', [ProductRecommendationController::class, 'related'])
        ->name('products.related');
    Route::get('products/{id}/frequently-bought-together', [ProductRecommendationController::class, 'frequentlyBoughtTogether'])
        ->name('products.frequently-bought-together');
    Route::get('products/trending', [ProductRecommendationController::class, 'trending'])
        ->name('products.trending');
    Route::get('products/popular', [ProductRecommendationController::class, 'popular'])
        ->name('products.popular');
    Route::get('products/new-arrivals', [ProductRecommendationController::class, 'newArrivals'])
        ->name('products.new-arrivals');
});

// Authenticated route for personalized recommendations
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('products/recommended', [ProductRecommendationController::class, 'personalized'])
        ->name('products.recommended');
});
