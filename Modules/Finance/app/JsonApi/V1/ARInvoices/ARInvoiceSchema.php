<?php

namespace Modules\Finance\JsonApi\V1\ARInvoices;

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
use Modules\Finance\Models\ARInvoice;

class ARInvoiceSchema extends Schema
{
    public static string $model = ARInvoice::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('contactId'),
            Str::make('invoiceNumber')->sortable(),
            DateTime::make('invoiceDate')->sortable(),
            DateTime::make('dueDate')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('exchangeRate')->sortable(),
            Number::make('subtotal')->sortable(),
            Number::make('taxTotal')->sortable(),
            Number::make('total')->sortable(),
            Str::make('status')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('invoice_number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
        ];
    }

    public function sortables(): array
    {
        return [
            'invoice_number',
            'invoice_date',
            'due_date',
            'currency',
            'exchange_rate',
            'subtotal',
            'tax_total',
            'total',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [

        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "a-r-invoices";
    }
}