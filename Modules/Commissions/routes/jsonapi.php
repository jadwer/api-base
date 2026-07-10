<?php

use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Commissions\Http\Controllers\Api\V1\CommissionController;

/*
|--------------------------------------------------------------------------
| Commissions Module JSON:API Routes
|--------------------------------------------------------------------------
|
| Commissions are read-only via JSON:API (index/show). Rows are created by
| the SalesOrder observer and updated by the Finance events listeners.
| Payout actions live in the custom endpoints below.
|
*/

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('commissions', CommissionController::class)
            ->only('index', 'show')
            ->readOnly();
    });

// Custom endpoints (reports + payout)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('commissions/by-period', [CommissionController::class, 'byPeriod'])->name('commissions.by-period');
    Route::get('commissions/by-employee', [CommissionController::class, 'byEmployee'])->name('commissions.by-employee');
    Route::post('commissions/pay-batch', [CommissionController::class, 'payBatch'])->name('commissions.pay-batch');
    Route::post('commissions/{id}/mark-paid', [CommissionController::class, 'markPaid'])
        ->whereNumber('id')
        ->name('commissions.mark-paid');
});
