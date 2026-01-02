<?php

use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Accounting\Http\Controllers\Api\V1\IdempotencyKeyController;
use Modules\Accounting\Http\Controllers\Api\V1\AccountMappingController;
use Modules\Accounting\Http\Controllers\Api\V1\AccountBalanceController;
use Modules\Accounting\Http\Controllers\Api\V1\ExchangeRatePolicyController;
use Modules\Accounting\Http\Controllers\Api\V1\AuditLogController;
use Modules\Accounting\Http\Controllers\Api\V1\AccountController;
use Modules\Accounting\Http\Controllers\Api\V1\FiscalPeriodController;
use Modules\Accounting\Http\Controllers\Api\V1\JournalController;
use Modules\Accounting\Http\Controllers\Api\V1\JournalSequenceController;
use Modules\Accounting\Http\Controllers\Api\V1\JournalEntryController;
use Modules\Accounting\Http\Controllers\Api\V1\JournalLineController;
use Modules\Accounting\Http\Controllers\Api\V1\ExchangeRateController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('idempotency-keys', IdempotencyKeyController::class);
        $server->resource('account-mappings', AccountMappingController::class);
        $server->resource('account-balances', AccountBalanceController::class);
        $server->resource('exchange-rate-policies', ExchangeRatePolicyController::class);
        $server->resource('audit-logs', AuditLogController::class);
        $server->resource('accounts', AccountController::class);
        $server->resource('fiscal-periods', FiscalPeriodController::class);
        $server->resource('journals', JournalController::class);
        $server->resource('journal-sequences', JournalSequenceController::class);
        $server->resource('journal-entries', JournalEntryController::class);
        $server->resource('journal-lines', JournalLineController::class);
        $server->resource('exchange-rates', ExchangeRateController::class);
    });

// AC-M001: Period Close Checklist custom endpoints
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('fiscal-periods/{fiscalPeriod}/close-checklist', [FiscalPeriodController::class, 'closeChecklist'])
        ->name('fiscal-periods.close-checklist');
    Route::post('fiscal-periods/{fiscalPeriod}/close', [FiscalPeriodController::class, 'close'])
        ->name('fiscal-periods.close');
    Route::post('fiscal-periods/{fiscalPeriod}/reopen', [FiscalPeriodController::class, 'reopen'])
        ->name('fiscal-periods.reopen');
    Route::get('fiscal-periods/{fiscalPeriod}/summary', [FiscalPeriodController::class, 'summary'])
        ->name('fiscal-periods.summary');
});
