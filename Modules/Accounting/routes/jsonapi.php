<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\Api\V1\AccountController;
use Modules\Accounting\Http\Controllers\Api\V1\FiscalPeriodController;
use Modules\Accounting\Http\Controllers\Api\V1\JournalController;
use Modules\Accounting\Http\Controllers\Api\V1\JournalEntryController;
use Modules\Accounting\Http\Controllers\Api\V1\JournalLineController;
use Modules\Accounting\Http\Controllers\Api\V1\ExchangeRateController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('accounts', AccountController::class);
        $server->resource('fiscal-periods', FiscalPeriodController::class);
        $server->resource('journals', JournalController::class);
        $server->resource('journal-entries', JournalEntryController::class);
        $server->resource('journal-lines', JournalLineController::class);
        $server->resource('exchange-rates', ExchangeRateController::class);
    });

// Custom action routes for GL operations
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Journal Entry Actions
    Route::post('journal-entries/{journal_entry}/post', [JournalEntryController::class, 'post'])
        ->name('v1.journal-entries.post');
    Route::get('journal-entries/{journal_entry}/totals', [JournalEntryController::class, 'totals'])
        ->name('v1.journal-entries.totals');
});
