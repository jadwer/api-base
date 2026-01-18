<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\BillingController;
use Modules\Billing\Http\Controllers\Api\V1\StripeWebhookController;
use Modules\Billing\Http\Controllers\Api\V1\StripeController;

// Stripe Webhook (NO AUTH - Stripe calls this endpoint)
Route::post('webhooks/stripe', [StripeWebhookController::class, 'handleWebhook'])->name('billing.webhooks.stripe');

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('billings', BillingController::class)->names('billing');

    // Stripe Payment Intents
    Route::prefix('stripe')->name('stripe.')->group(function () {
        // Payment Intents
        Route::post('payment-intents', [StripeController::class, 'store'])->name('payment-intents.store');
        Route::get('payment-intents/{id}', [StripeController::class, 'show'])->name('payment-intents.show');
        Route::post('payment-intents/{id}/confirm', [StripeController::class, 'confirm'])->name('payment-intents.confirm');
        Route::post('payment-intents/{id}/capture', [StripeController::class, 'capture'])->name('payment-intents.capture');
        Route::post('payment-intents/{id}/cancel', [StripeController::class, 'cancel'])->name('payment-intents.cancel');

        // Refunds
        Route::post('refunds', [StripeController::class, 'refund'])->name('refunds.store');
    });
});
