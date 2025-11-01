<?php

namespace Modules\Billing\JsonApi\V1\PaymentTransactions;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Billing\Models\PaymentTransaction;

class PaymentTransactionSchema extends Schema
{
    public static string $model = PaymentTransaction::class;

    protected int $maxDepth = 3;

    /**
     * MANDATORY: Define ALL resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign keys (for creation/updates)
            Number::make('checkoutSessionId', 'checkout_session_id'),
            Number::make('salesOrderId', 'sales_order_id'),
            Number::make('arInvoiceId', 'ar_invoice_id'),

            // Gateway Info
            Str::make('gateway')->sortable(),
            Str::make('paymentIntentId', 'payment_intent_id')->sortable(),
            Str::make('transactionId', 'transaction_id')->sortable(),
            Str::make('clientSecret', 'client_secret')->hidden(), // Sensitive data

            // Payment Details
            Number::make('amount')->sortable(),
            Str::make('currency')->sortable(),
            Str::make('status')->sortable(),
            Str::make('paymentMethod', 'payment_method')->sortable(),

            // Card Info
            Str::make('cardBrand', 'card_brand'),
            Str::make('cardLast4', 'card_last4'),

            // Gateway Response & Error
            ArrayHash::make('gatewayResponse', 'gateway_response'),
            Str::make('errorMessage', 'error_message'),

            // Event Timestamps
            DateTime::make('authorizedAt', 'authorized_at')->sortable(),
            DateTime::make('capturedAt', 'captured_at')->sortable(),
            DateTime::make('failedAt', 'failed_at')->sortable(),
            DateTime::make('refundedAt', 'refunded_at')->sortable(),

            // Metadata
            ArrayHash::make('metadata'),

            // Relationships
            BelongsTo::make('checkoutSession')->type('checkout-sessions'),
            BelongsTo::make('salesOrder')->type('sales-orders'),
            BelongsTo::make('arInvoice')->type('ar-invoices'),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * MANDATORY: Define filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('gateway'),
            Where::make('paymentIntentId', 'payment_intent_id'),
            Where::make('transactionId', 'transaction_id'),
            Where::make('status'),
            Where::make('paymentMethod', 'payment_method'),
            Where::make('currency'),
            Where::make('checkoutSessionId', 'checkout_session_id'),
            Where::make('salesOrderId', 'sales_order_id'),
            Where::make('arInvoiceId', 'ar_invoice_id'),
        ];
    }

    /**
     * MANDATORY: Define pagination.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
