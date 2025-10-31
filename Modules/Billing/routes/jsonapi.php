<?php

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $api) {

        // Payment Transactions (Phase 1)
        $api->resource('payment-transactions', \Modules\Billing\Http\Controllers\Api\V1\PaymentTransactionController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('checkoutSession');
                $relationships->hasOne('salesOrder');
                $relationships->hasOne('arInvoice');
            });

        // Company Settings (Phase 2)
        $api->resource('company-settings', \Modules\Billing\Http\Controllers\Api\V1\CompanySettingController::class);

        // CFDI Invoices (Phase 2)
        $api->resource('cfdi-invoices', \Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('companySetting');
                $relationships->hasOne('contact');
                $relationships->hasOne('arInvoice');
                $relationships->hasMany('items');
            });

        // CFDI Items (Phase 2)
        $api->resource('cfdi-items', \Modules\Billing\Http\Controllers\Api\V1\CFDIItemController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('cfdiInvoice');
                $relationships->hasOne('product');
            });
    });
