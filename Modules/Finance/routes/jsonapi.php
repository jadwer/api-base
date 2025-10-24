<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Finance\Http\Controllers\Api\V1\ARInvoiceController;
use Modules\Finance\Http\Controllers\Api\V1\APInvoiceController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentApplicationController;
use Modules\Finance\Http\Controllers\Api\V1\BankAccountController;
use Modules\Finance\Http\Controllers\Api\V1\PaymentMethodController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('a-r-invoices', ARInvoiceController::class);
        $server->resource('a-p-invoices', APInvoiceController::class);
        $server->resource('payments', PaymentController::class);
        $server->resource('payment-applications', PaymentApplicationController::class);
        $server->resource('bank-accounts', BankAccountController::class);
        $server->resource('payment-methods', PaymentMethodController::class);
    });
