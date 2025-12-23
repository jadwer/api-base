<?php

use Illuminate\Support\Facades\Route;
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

// Virtual Reports - Use regular Laravel routes instead of JSON:API routes
// because reports are virtual resources (no database models)
// Note: RouteServiceProvider adds 'api' prefix already
Route::prefix('v1/reports')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // Financial Statements
        Route::get('balance-sheets', [BalanceSheetController::class, 'index']);
        Route::get('balance-sheets/{id}', [BalanceSheetController::class, 'show']);

        Route::get('income-statements', [IncomeStatementController::class, 'index']);
        Route::get('income-statements/{id}', [IncomeStatementController::class, 'show']);

        Route::get('cash-flows', [CashFlowController::class, 'index']);
        Route::get('cash-flows/{id}', [CashFlowController::class, 'show']);

        Route::get('trial-balances', [TrialBalanceController::class, 'index']);
        Route::get('trial-balances/{id}', [TrialBalanceController::class, 'show']);

        // Aging Reports
        Route::get('ar-aging-reports', [ARAgingReportController::class, 'index']);
        Route::get('ar-aging-reports/{id}', [ARAgingReportController::class, 'show']);

        Route::get('ap-aging-reports', [APAgingReportController::class, 'index']);
        Route::get('ap-aging-reports/{id}', [APAgingReportController::class, 'show']);

        // Sales Reports
        Route::get('sales-by-customer-reports', [SalesByCustomerReportController::class, 'index']);
        Route::get('sales-by-customer-reports/{id}', [SalesByCustomerReportController::class, 'show']);

        Route::get('sales-by-product-reports', [SalesByProductReportController::class, 'index']);
        Route::get('sales-by-product-reports/{id}', [SalesByProductReportController::class, 'show']);

        // Purchase Reports
        Route::get('purchase-by-supplier-reports', [PurchaseBySupplierReportController::class, 'index']);
        Route::get('purchase-by-supplier-reports/{id}', [PurchaseBySupplierReportController::class, 'show']);

        Route::get('purchase-by-product-reports', [PurchaseByProductReportController::class, 'index']);
        Route::get('purchase-by-product-reports/{id}', [PurchaseByProductReportController::class, 'show']);
    });
