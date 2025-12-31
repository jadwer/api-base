<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Finance\Http\Controllers\Api\V1\ARInvoiceController;
use Modules\Finance\Http\Controllers\Api\V1\APInvoiceController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentApplicationController;
use Modules\Finance\Http\Controllers\Api\V1\BankAccountController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentMethodController;
use Modules\Finance\Http\Controllers\Api\V1\ARPaymentController;

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
    });
