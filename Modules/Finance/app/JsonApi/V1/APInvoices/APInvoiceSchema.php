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
            
            Str::make('invoiceNumber')->sortable(),
            DateTime::make('invoiceDate')->sortable(),
            DateTime::make('dueDate')->sortable(),
            Number::make('contactId')->sortable(),
            Number::make('purchaseOrderId')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('subtotal')->sortable(),
            Number::make('taxAmount')->sortable(),
            Number::make('totalAmount')->sortable(),
            Number::make('paidAmount')->sortable(),
            Str::make('status')->sortable(),
            Number::make('journalEntryId')->sortable(),
            Str::make('notes'),
            ArrayHash::make('metadata'),
            Boolean::make('isActive')->sortable(),

            // PU-M002: Reconciliation fields
            Str::make('reconciliationStatus')->sortable(),
            DateTime::make('reconciledAt')->sortable(),
            Number::make('reconciledBy')->sortable(),
            Str::make('reconciliationNotes'),
            ArrayHash::make('discrepancies'),

            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('contact'),
            BelongsTo::make('purchaseOrder'),
            BelongsTo::make('journalEntry'),
            BelongsTo::make('reconciledBy', 'reconciledBy')->type('users'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('invoice_number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('contact_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('purchase_order_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('journal_entry_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_active'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('reconciliation_status'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('reconciled_by'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'contact',
            'purchaseOrder',
            'journalEntry',
            'reconciledBy',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "ap-invoices";
    }
}