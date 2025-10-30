<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Reports\Http\Controllers\Api\V1\BalanceSheetController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        // Financial Statements - Read-only reports
        $server->resource('balance-sheets', BalanceSheetController::class)
            ->readOnly(); // Reports are generated, not created/updated/deleted

        // Additional reports will be added here:
        // $server->resource('income-statements', IncomeStatementController::class)->readOnly();
        // $server->resource('cash-flows', CashFlowController::class)->readOnly();
        // $server->resource('trial-balances', TrialBalanceController::class)->readOnly();
        // etc.
    });
