<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Api\V1\LotTraceabilityController;
use Modules\Inventory\Http\Controllers\Api\V1\FractionationApiController;

/*
|--------------------------------------------------------------------------
| Inventory API Routes (non-JSON:API)
|--------------------------------------------------------------------------
|
| IV-M003: Lot Traceability endpoints
|
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Lot Traceability endpoints
    Route::prefix('lot-traceability')->group(function () {
        // Batch-specific endpoints
        Route::get('{batch}/genealogy', [LotTraceabilityController::class, 'genealogy'])
            ->name('lot-traceability.genealogy');
        Route::get('{batch}/trace-backward', [LotTraceabilityController::class, 'traceBackward'])
            ->name('lot-traceability.trace-backward');
        Route::get('{batch}/trace-forward', [LotTraceabilityController::class, 'traceForward'])
            ->name('lot-traceability.trace-forward');
        Route::get('{batch}/recall-impact', [LotTraceabilityController::class, 'recallImpact'])
            ->name('lot-traceability.recall-impact');
        Route::post('{batch}/initiate-recall', [LotTraceabilityController::class, 'initiateRecall'])
            ->name('lot-traceability.initiate-recall');

        // General endpoints
        Route::post('select-fefo', [LotTraceabilityController::class, 'selectFEFO'])
            ->name('lot-traceability.select-fefo');
        Route::get('expiring-soon', [LotTraceabilityController::class, 'expiringSoon'])
            ->name('lot-traceability.expiring-soon');
        Route::get('expired', [LotTraceabilityController::class, 'expired'])
            ->name('lot-traceability.expired');
        Route::get('search', [LotTraceabilityController::class, 'search'])
            ->name('lot-traceability.search');
    });

    // Fractionation custom endpoints
    Route::prefix('fraccionamiento')->group(function () {
        Route::post('calculate', [FractionationApiController::class, 'calculate'])
            ->name('fraccionamiento.calculate');
        Route::post('execute', [FractionationApiController::class, 'execute'])
            ->name('fraccionamiento.execute');
    });
});
