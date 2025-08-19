<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use Modules\Finance\Http\Controllers\Api\V1\BankAccountController;
use Modules\Finance\Http\Controllers\Api\V1\BankStatementController;
use Modules\Finance\Http\Controllers\Api\V1\BankStatementLineController;
use Modules\Finance\Http\Controllers\Api\V1\APInvoiceController;
use Modules\Finance\Http\Controllers\Api\V1\APInvoiceLineController;
use Modules\Finance\Http\Controllers\Api\V1\APPaymentController;
use Modules\Finance\Http\Controllers\Api\V1\APInvoicePaymentController;
use Modules\Finance\Http\Controllers\Api\V1\ARInvoiceController;
use Modules\Finance\Http\Controllers\Api\V1\ARInvoiceLineController;
use Modules\Finance\Http\Controllers\Api\V1\ARReceiptController;
use Modules\Finance\Http\Controllers\Api\V1\ARInvoiceReceiptController;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('bank-accounts', BankAccountController::class);
        $server->resource('bank-statements', BankStatementController::class);
        $server->resource('bank-statement-lines', BankStatementLineController::class);
        $server->resource('a-p-invoices', APInvoiceController::class);
        $server->resource('a-p-invoice-lines', APInvoiceLineController::class);
        $server->resource('a-p-payments', APPaymentController::class);
        $server->resource('a-p-invoice-payments', APInvoicePaymentController::class);
        $server->resource('a-r-invoices', ARInvoiceController::class);
        $server->resource('a-r-invoice-lines', ARInvoiceLineController::class);
        $server->resource('a-r-receipts', ARReceiptController::class);
        $server->resource('a-r-invoice-receipts', ARInvoiceReceiptController::class);
    });
