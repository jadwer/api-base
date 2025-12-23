<?php

namespace Modules\Ecommerce\JsonApi\V1\PaymentTransactions;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\PaymentTransaction;

class PaymentTransactionSchema extends Schema
{
    public static string $model = PaymentTransaction::class;

    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('checkoutSessionId', 'checkout_session_id'),
            Number::make('salesOrderId', 'sales_order_id'),
            Number::make('arInvoiceId', 'ar_invoice_id'),

            Str::make('transactionId', 'transaction_id'),
            Str::make('gateway'),
            Str::make('paymentMethod', 'payment_method'),
            Str::make('status'),

            Number::make('amount'),
            Str::make('currency'),

            ArrayHash::make('gatewayResponse', 'gateway_response'),
            Str::make('errorMessage', 'error_message'),
            ArrayHash::make('metadata'),

            DateTime::make('processedAt', 'processed_at'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            Boolean::make('isSuccessful', 'is_successful')->readOnly(),
            Boolean::make('isFailed', 'is_failed')->readOnly(),
            Boolean::make('isRefunded', 'is_refunded')->readOnly(),

            BelongsTo::make('checkoutSession')->type('checkout-sessions')->readOnly(),
            BelongsTo::make('salesOrder')->type('sales-orders')->readOnly(),
            BelongsTo::make('arInvoice')->type('ar-invoices')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('gateway'),
            Where::make('checkoutSessionId', 'checkout_session_id'),
            Where::make('salesOrderId', 'sales_order_id'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
