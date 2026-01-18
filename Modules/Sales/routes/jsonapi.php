<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Sales\Http\Controllers\Api\V1\SalesOrderController;
use Modules\Sales\Http\Controllers\Api\V1\SalesOrderItemController;
use Modules\Sales\Http\Controllers\Api\V1\ShipmentController;
use Modules\Sales\Http\Controllers\Api\V1\ShipmentItemController;
use Modules\Sales\Http\Controllers\Api\V1\BackorderController;
use Modules\Sales\Http\Controllers\Api\V1\DiscountRuleController;
use Modules\Sales\Http\Controllers\Api\V1\QuoteController;
use Modules\Sales\Http\Controllers\Api\V1\QuoteItemController;
use Illuminate\Support\Facades\Route;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('sales-orders', SalesOrderController::class);
        $server->resource('sales-order-items', SalesOrderItemController::class);
        // SA-M001: Partial Shipment Support
        $server->resource('shipments', ShipmentController::class);
        $server->resource('shipment-items', ShipmentItemController::class);
        // SA-M002: Backorder Management
        $server->resource('backorders', BackorderController::class);
        // SA-M003: Automatic Discount Rules
        $server->resource('discount-rules', DiscountRuleController::class);
        // SA-M004: Quote Management
        $server->resource('quotes', QuoteController::class);
        $server->resource('quote-items', QuoteItemController::class);
    });

// Custom endpoints for sales reporting
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('sales-orders/reports', [SalesOrderController::class, 'reports'])->name('sales-orders.reports');
    Route::get('sales-orders/customers', [SalesOrderController::class, 'customers'])->name('sales-orders.customers');

    // SA-M001: Shipment action endpoints
    Route::post('shipments/create-from-order', [ShipmentController::class, 'createFromOrder'])->name('shipments.create-from-order');
    Route::post('shipments/{shipment}/ship', [ShipmentController::class, 'ship'])->name('shipments.ship');
    Route::post('shipments/{shipment}/deliver', [ShipmentController::class, 'deliver'])->name('shipments.deliver');
    Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');
    Route::get('sales-orders/{order}/shipment-summary', [ShipmentController::class, 'orderSummary'])->name('sales-orders.shipment-summary');

    // SA-M002: Backorder action endpoints
    Route::post('backorders/{backorder}/fulfill', [BackorderController::class, 'fulfill'])->name('backorders.fulfill');
    Route::post('backorders/{backorder}/cancel', [BackorderController::class, 'cancel'])->name('backorders.cancel');
    Route::get('sales-orders/{order}/backorder-summary', [BackorderController::class, 'orderSummary'])->name('sales-orders.backorder-summary');
    Route::post('backorders/fulfill-for-product', [BackorderController::class, 'fulfillForProduct'])->name('backorders.fulfill-for-product');
    Route::get('backorders/pending-for-product/{productId}', [BackorderController::class, 'pendingForProduct'])->name('backorders.pending-for-product');

    // SA-M004: Quote action endpoints
    Route::post('quotes/from-cart', [QuoteController::class, 'createFromCart'])->name('quotes.from-cart');
    Route::get('quotes/expiring-soon', [QuoteController::class, 'expiringSoon'])->name('quotes.expiring-soon');
    Route::get('quotes/summary', [QuoteController::class, 'summary'])->name('quotes.summary');
    Route::post('quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');
    Route::post('quotes/{quote}/accept', [QuoteController::class, 'accept'])->name('quotes.accept');
    Route::post('quotes/{quote}/reject', [QuoteController::class, 'reject'])->name('quotes.reject');
    Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
    Route::post('quotes/{quote}/cancel', [QuoteController::class, 'cancel'])->name('quotes.cancel');
    Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
});
