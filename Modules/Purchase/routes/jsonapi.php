<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Purchase\Http\Controllers\Api\V1\PurchaseOrderController;
use Modules\Purchase\Http\Controllers\Api\V1\PurchaseOrderItemController;
use Modules\Purchase\Http\Controllers\Api\V1\BudgetController;
use Modules\Purchase\Http\Controllers\Api\V1\BudgetAllocationController;
use Illuminate\Support\Facades\Route;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('purchase-orders', PurchaseOrderController::class);
        $server->resource('purchase-order-items', PurchaseOrderItemController::class);
        // PU-M003: Budget Control
        $server->resource('budgets', BudgetController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('category');
                $relationships->hasOne('contact');
                $relationships->hasMany('allocations');
            });
        $server->resource('budget-allocations', BudgetAllocationController::class);
    });

// Custom endpoints for purchase reporting and approval
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('purchase-orders/reports', [PurchaseOrderController::class, 'reports'])->name('purchase-orders.reports');
    Route::get('purchase-orders/suppliers', [PurchaseOrderController::class, 'suppliers'])->name('purchase-orders.suppliers');

    // Approval workflow endpoints
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');

    // Refactor ciclo (Patron 1/2): endpoints de accion que antes no existian.
    // La recepcion y la cancelacion solo se lograban por PATCH directo de status,
    // saltando la validacion de tolerancia (receive) y la reversa de stock/APInvoice (cancel).
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    // PU-M003: Budget Control endpoints
    Route::get('budgets/summary', [BudgetController::class, 'summary'])->name('budgets.summary');
    Route::get('budgets/needs-attention', [BudgetController::class, 'needsAttention'])->name('budgets.needs-attention');
});
