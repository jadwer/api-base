<?php

use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Finance\Http\Controllers\Api\V1\ARInvoiceController;
use Modules\Finance\Http\Controllers\Api\V1\APInvoiceController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentApplicationController;
use Modules\Finance\Http\Controllers\Api\V1\BankAccountController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentMethodController;
use Modules\Finance\Http\Controllers\Api\V1\ARPaymentController;
use Modules\Finance\Http\Controllers\Api\V1\BankTransactionController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('ar-invoices', ARInvoiceController::class);
        $server->resource('ap-invoices', APInvoiceController::class);
        $server->resource('payments', PaymentController::class);
        $server->resource('payment-applications', PaymentApplicationController::class);
        $server->resource('bank-accounts', BankAccountController::class);
        $server->resource('payment-methods', PaymentMethodController::class);
        $server->resource('ar-payments', ARPaymentController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('contact');
                $relationships->hasOne('fiscalPeriod');
                $relationships->hasOne('bankAccount');
                $relationships->hasOne('journalEntry');
                $relationships->hasMany('applications');
            });
        $server->resource('bank-transactions', BankTransactionController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('bankAccount');
                $relationships->hasOne('reconciledBy');
            });
    });

// FI-M001: Late Payment Penalty endpoints
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('ar-invoices/{arInvoice}/late-penalty', [ARInvoiceController::class, 'latePenalty'])
        ->name('ar-invoices.late-penalty');
    Route::post('ar-invoices/{arInvoice}/apply-penalty', [ARInvoiceController::class, 'applyPenalty'])
        ->name('ar-invoices.apply-penalty');
    Route::get('ar-invoices/overdue-with-penalties', [ARInvoiceController::class, 'overdueWithPenalties'])
        ->name('ar-invoices.overdue-with-penalties');
    Route::get('ar-invoices/penalty-summary', [ARInvoiceController::class, 'penaltySummary'])
        ->name('ar-invoices.penalty-summary');
    Route::get('ar-invoices/aging-report', [ARInvoiceController::class, 'agingReport'])
        ->name('ar-invoices.aging-report');

    // Fase B CxC: registro de pago directo sobre factura AR
    Route::post('ar-invoices/{arInvoice}/register-payment', [ARInvoiceController::class, 'registerPayment'])
        ->name('ar-invoices.register-payment');
});
