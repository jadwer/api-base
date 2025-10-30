<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Reports\Http\Controllers\Api\V1\BalanceSheetController;
use Modules\Reports\Http\Controllers\Api\V1\IncomeStatementController;
use Modules\Reports\Http\Controllers\Api\V1\CashFlowController;
use Modules\Reports\Http\Controllers\Api\V1\TrialBalanceController;
use Modules\Reports\Http\Controllers\Api\V1\ARAgingReportController;
use Modules\Reports\Http\Controllers\Api\V1\APAgingReportController;
use Modules\Reports\Http\Controllers\Api\V1\SalesByCustomerReportController;
use Modules\Reports\Http\Controllers\Api\V1\SalesByProductReportController;
use Modules\Reports\Http\Controllers\Api\V1\PurchaseBySupplierReportController;
use Modules\Reports\Http\Controllers\Api\V1\PurchaseByProductReportController;

JsonApiRoute::server('v1')
    ->prefix('v1/reports')  // Reports under /api/v1/reports/*
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        // Financial Statements - Read-only reports (Phase 4.2 - Corrected)
        $server->resource('balance-sheets', BalanceSheetController::class)->readOnly();
        $server->resource('income-statements', IncomeStatementController::class)->readOnly();
        $server->resource('cash-flows', CashFlowController::class)->readOnly();
        $server->resource('trial-balances', TrialBalanceController::class)->readOnly();

        // Aging Reports
        $server->resource('ar-aging-reports', ARAgingReportController::class)->readOnly();
        $server->resource('ap-aging-reports', APAgingReportController::class)->readOnly();

        // Sales Reports
        $server->resource('sales-by-customer-reports', SalesByCustomerReportController::class)->readOnly();
        $server->resource('sales-by-product-reports', SalesByProductReportController::class)->readOnly();

        // Purchase Reports
        $server->resource('purchase-by-supplier-reports', PurchaseBySupplierReportController::class)->readOnly();
        $server->resource('purchase-by-product-reports', PurchaseByProductReportController::class)->readOnly();
    });
