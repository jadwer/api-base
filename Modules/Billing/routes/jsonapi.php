<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $api) {

        // Payment Transactions (Phase 1)
        // $api->resource('payment-transactions', \Modules\Billing\Http\Controllers\Api\V1\PaymentTransactionController::class);

        // CFDI Invoices (Phase 2 - placeholder)
        // $api->resource('cfdi-invoices', \Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class);

        // Company Settings (Phase 2 - placeholder)
        // $api->resource('company-settings', \Modules\Billing\Http\Controllers\Api\V1\CompanySettingController::class);
    });
