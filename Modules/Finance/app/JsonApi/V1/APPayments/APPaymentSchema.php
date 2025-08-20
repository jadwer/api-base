<?php

namespace Modules\Finance\JsonApi\V1\APPayments;

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
use Modules\Finance\Models\APPayment;

class APPaymentSchema extends Schema
{
    public static string $model = APPayment::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            // Foreign keys
            Number::make('contact_id'),
            Number::make('ap_invoice_id'),
            Number::make('bank_account_id'),
            
            // Relationships
            BelongsTo::make('contact')->type('contacts'),
            BelongsTo::make('apInvoice')->type('a-p-invoices'),
            BelongsTo::make('bankAccount')->type('bank-accounts'),
            
            // Basic fields
            DateTime::make('payment_date')->sortable(),
            Str::make('payment_method')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('amount')->sortable(),
            Str::make('status')->sortable(),
            HasMany::make('aPInvoicePayments')->type('a-p-invoice-payments'),
            
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('payment_method'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
        ];
    }


    public function includePaths(): array
    {
        return [
            'contact',
            'apInvoice',
            'bankAccount', 
            'aPInvoicePayments',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "a-p-payments";
    }
}