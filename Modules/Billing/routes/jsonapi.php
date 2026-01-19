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

        // Invoice Series (Phase 11 - Multiple series support: FAC, FAC-W, REFAC, N)
        $api->resource('invoice-series', \Modules\Billing\Http\Controllers\Api\V1\InvoiceSeriesController::class)
            ->relationships(function ($relationships) {
                $relationships->hasOne('companySetting');
            });
    });

// CFDI Generation Endpoints (Custom actions outside JSON:API spec)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('cfdi-invoices/{cfdiInvoice}/generate-xml', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'generateXml']);
    Route::post('cfdi-invoices/{cfdiInvoice}/generate-pdf', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'generatePdf']);
    Route::get('cfdi-invoices/{cfdiInvoice}/download-pdf', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'downloadPdf']);
    Route::get('cfdi-invoices/{cfdiInvoice}/preview-pdf', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'previewPdf']);
    Route::get('cfdi-invoices/{cfdiInvoice}/download-xml', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'downloadXml']);
    Route::post('cfdi-invoices/{cfdiInvoice}/send-email', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'sendEmail']);

    // PAC Integration Endpoints (Phase 3)
    Route::post('cfdi-invoices/{cfdiInvoice}/stamp', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'stamp']);
    Route::post('cfdi-invoices/{cfdiInvoice}/cancel', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'cancel']);
    Route::get('cfdi-invoices/{cfdiInvoice}/validate-sat', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'validateSAT']);
    Route::get('cfdi-invoices/{cfdiInvoice}/cancellation-status', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'cancellationStatus']);

    // Prefactura Endpoints (Phase 11 - Invoice Preview)
    Route::get('cfdi-invoices/{cfdiInvoice}/prefactura', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'prefactura']);
    Route::get('cfdi-invoices/{cfdiInvoice}/prefactura/download', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'downloadPrefactura']);
    Route::get('cfdi-invoices/{cfdiInvoice}/prefactura/preview', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'previewPrefactura']);

    // Prefactura from SalesOrder (without creating CFDIInvoice)
    Route::get('sales-orders/{salesOrder}/prefactura', [\Modules\Billing\Http\Controllers\Api\V1\CFDIInvoiceController::class, 'prefacturaFromOrder']);

    // Invoice Series Custom Endpoints (Phase 11)
    Route::get('invoice-series/available', [\Modules\Billing\Http\Controllers\Api\V1\InvoiceSeriesController::class, 'available']);
    Route::get('invoice-series/summary', [\Modules\Billing\Http\Controllers\Api\V1\InvoiceSeriesController::class, 'summary']);
    Route::post('invoice-series/initialize-defaults', [\Modules\Billing\Http\Controllers\Api\V1\InvoiceSeriesController::class, 'initializeDefaults']);
    Route::get('invoice-series/{invoiceSeries}/preview-next-folio', [\Modules\Billing\Http\Controllers\Api\V1\InvoiceSeriesController::class, 'previewNextFolio']);
    Route::post('invoice-series/{invoiceSeries}/set-initial-folio', [\Modules\Billing\Http\Controllers\Api\V1\InvoiceSeriesController::class, 'setInitialFolio']);
    Route::post('invoice-series/{invoiceSeries}/set-as-default', [\Modules\Billing\Http\Controllers\Api\V1\InvoiceSeriesController::class, 'setAsDefault']);
});

// Company Settings - Certificate and PAC Endpoints (Phase 9)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('company-settings/{companySetting}/upload-certificate', [\Modules\Billing\Http\Controllers\Api\V1\CompanySettingController::class, 'uploadCertificate']);
    Route::post('company-settings/{companySetting}/upload-key', [\Modules\Billing\Http\Controllers\Api\V1\CompanySettingController::class, 'uploadKey']);
    Route::post('company-settings/{companySetting}/test-pac', [\Modules\Billing\Http\Controllers\Api\V1\CompanySettingController::class, 'testPac']);
});

// PAC Webhook Endpoints (No auth required - validated by signature)
Route::prefix('v1/webhooks/pac')->group(function () {
    Route::post('stamp', [\Modules\Billing\Http\Controllers\Api\V1\PacWebhookController::class, 'stampNotification']);
    Route::post('cancel', [\Modules\Billing\Http\Controllers\Api\V1\PacWebhookController::class, 'cancelNotification']);
});
