<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\BillingController;
use Modules\Billing\Http\Controllers\Api\V1\StripeWebhookController;

// Stripe Webhook (NO AUTH - Stripe calls this endpoint)
Route::post('webhooks/stripe', [StripeWebhookController::class, 'handleWebhook'])->name('billing.webhooks.stripe');

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('billings', BillingController::class)->names('billing');
});
