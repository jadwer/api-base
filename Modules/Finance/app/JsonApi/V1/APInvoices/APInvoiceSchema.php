<?php

namespace Modules\Finance\JsonApi\V1\APInvoices;

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
use Modules\Finance\Models\APInvoice;

class APInvoiceSchema extends Schema
{
    public static string $model = APInvoice::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('contactId', 'contact_id'),
            
            // Relationship to Contact
            BelongsTo::make('contact')->type('contacts'),
            
            Str::make('invoiceNumber', 'invoice_number')->sortable(),
            DateTime::make('invoiceDate', 'invoice_date')->sortable(),
            DateTime::make('dueDate', 'due_date')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('exchangeRate', 'exchange_rate')->sortable(),
            Number::make('subtotal')->sortable(),
            Number::make('taxTotal', 'tax_total')->sortable(),
            Number::make('total')->sortable(),
            Str::make('status')->sortable(),
            
            // F1 Calculated fields (explicit appends)
            Number::make('paidAmount')->readOnly(),
            Number::make('remainingBalance')->readOnly(),
            
            // Metadata
            ArrayHash::make('metadata'),
            
            // Relationships
            HasMany::make('aPInvoiceLines')->type('a-p-invoice-lines'),
            HasMany::make('aPInvoicePayments')->type('a-p-invoice-payments'),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('invoice_number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
        ];
    }


    public function includePaths(): array
    {
        return [
            'contact',
            'aPInvoiceLines',
            'aPInvoicePayments',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "a-p-invoices";
    }
}