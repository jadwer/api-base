<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Modules\Reports\Http\Controllers\Api\V1\BalanceSheetController;
use Modules\Reports\Services\ManagementReports\AgingReportService;
use Modules\Reports\Http\Controllers\Api\V1\IncomeStatementController;
use Modules\Reports\Http\Controllers\Api\V1\CashFlowController;
use Modules\Reports\Http\Controllers\Api\V1\TrialBalanceController;
use Modules\Reports\Http\Controllers\Api\V1\AgingReportController;
use Modules\Reports\Http\Controllers\Api\V1\SalesReportController;
use Modules\Reports\Http\Controllers\Api\V1\SalesAdvancedReportController;
use Modules\Reports\Http\Controllers\Api\V1\PurchaseReportController;
use Modules\Reports\Http\Controllers\Api\V1\AnalyticsController;
use Modules\Reports\Http\Controllers\Api\V1\ExportController;

/*
|--------------------------------------------------------------------------
| Reports Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('reports')->group(function () {

        // Financial Statements
        Route::get('balance-sheet', [BalanceSheetController::class, 'index']);
        Route::get('balance-sheet/comparative', [BalanceSheetController::class, 'comparative']);

        Route::get('income-statement', [IncomeStatementController::class, 'index']);
        Route::get('income-statement/comparative', [IncomeStatementController::class, 'comparative']);

        Route::get('cash-flow', [CashFlowController::class, 'index']);
        Route::get('cash-flow/comparative', [CashFlowController::class, 'comparative']);

        Route::get('trial-balance', [TrialBalanceController::class, 'index']);
        Route::get('trial-balance/comparative', [TrialBalanceController::class, 'comparative']);
        Route::get('trial-balance/detailed', [TrialBalanceController::class, 'detailed']);

        // Management Reports
        Route::get('aging-ar', [AgingReportController::class, 'ar']);
        Route::get('aging-ap', [AgingReportController::class, 'ap']);

        Route::get('sales-by-customer', [SalesReportController::class, 'byCustomer']);
        Route::get('sales-by-product', [SalesReportController::class, 'byProduct']);

        Route::get('purchase-by-supplier', [PurchaseReportController::class, 'bySupplier']);
        Route::get('purchase-by-product', [PurchaseReportController::class, 'byProduct']);

        // Phase 13: Advanced Sales Reports
        Route::get('sales-by-employee', [SalesAdvancedReportController::class, 'byEmployee']);
        Route::get('sales-by-batch', [SalesAdvancedReportController::class, 'byBatch']);
        Route::get('sales-profitability', [SalesAdvancedReportController::class, 'profitability']);
        Route::get('sales-trend', [SalesAdvancedReportController::class, 'trend']);

    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('kpis', [AnalyticsController::class, 'kpis']);
        Route::get('metrics', [AnalyticsController::class, 'metrics']);
        Route::get('trends', [AnalyticsController::class, 'trends']);
        Route::get('dashboard', [AnalyticsController::class, 'dashboard']);
    });

    // Export Routes
    Route::prefix('reports')->group(function () {
        Route::get('balance-sheet/export', [ExportController::class, 'balanceSheet']);
        Route::get('income-statement/export', [ExportController::class, 'incomeStatement']);
        Route::get('cash-flow/export', [ExportController::class, 'cashFlow']);
        Route::get('trial-balance/export', [ExportController::class, 'trialBalance']);
        Route::get('aging-ar/export', fn(Request $request, AgingReportService $service) =>
            app(ExportController::class)->aging($request, $service, 'ar'));
        Route::get('aging-ap/export', fn(Request $request, AgingReportService $service) =>
            app(ExportController::class)->aging($request, $service, 'ap'));
        Route::get('sales-by-customer/export', [ExportController::class, 'salesByCustomer']);

        // Phase 13: Advanced Report Exports
        Route::get('sales-by-employee/export', [ExportController::class, 'salesByEmployee']);
        Route::get('sales-by-batch/export', [ExportController::class, 'salesByBatch']);
        Route::get('sales-profitability/export', [ExportController::class, 'salesProfitability']);
    });
});
