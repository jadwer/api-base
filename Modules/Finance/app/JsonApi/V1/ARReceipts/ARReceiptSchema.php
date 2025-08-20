<?php

namespace Modules\Finance\JsonApi\V1\ARReceipts;

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
use Modules\Finance\Models\ARReceipt;

class ARReceiptSchema extends Schema
{
    public static string $model = ARReceipt::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            // Foreign keys
            Number::make('contact_id'),
            Number::make('ar_invoice_id'),
            Number::make('bank_account_id'),
            
            // Relationships
            BelongsTo::make('contact')->type('contacts'),
            BelongsTo::make('arInvoice')->type('a-r-invoices'),
            BelongsTo::make('bankAccount')->type('bank-accounts'),
            
            // Basic fields
            DateTime::make('receipt_date')->sortable(),
            Str::make('payment_method')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('amount')->sortable(),
            Str::make('status')->sortable(),
            
            HasMany::make('aRInvoiceReceipts')->type('a-r-invoice-receipts'),
            
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

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
            'arInvoice', 
            'bankAccount',
            'aRInvoiceReceipts',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "a-r-receipts";
    }
}