<?php

namespace Modules\Finance\JsonApi\V1\PaymentApplications;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Finance\Models\PaymentApplication;

class PaymentApplicationSchema extends Schema
{
    public static string $model = PaymentApplication::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('paymentId')->sortable(),
            Number::make('arInvoiceId')->sortable(),
            Number::make('amount')->sortable(),
            DateTime::make('applicationDate')->sortable(),
            Str::make('notes'),
            Boolean::make('isActive')->sortable(),
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('payment'),
            // Relationships
            BelongsTo::make('aRInvoice'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('payment_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('ar_invoice_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('isActive', 'is_active'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'payment',
            'aRInvoice',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "payment-applications";
    }
}