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
            
            Str::make('invoiceNumber')->sortable(),
            DateTime::make('invoiceDate')->sortable(),
            DateTime::make('dueDate')->sortable(),
            Number::make('contactId')->sortable(),
            Number::make('salesOrderId')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('subtotal')->sortable(),
            Number::make('taxAmount')->sortable(),
            Number::make('totalAmount')->sortable(),
            Number::make('paidAmount')->sortable(),
            DateTime::make('paidDate')->sortable(),
            Str::make('status')->sortable(),
            Number::make('journalEntryId')->sortable(),
            Str::make('notes'),
            ArrayHash::make('metadata'),
            Boolean::make('isActive')->sortable(),

            // FI-M002: Early payment discount fields
            Number::make('discountPercent')->sortable(),
            Number::make('discountDays'),
            DateTime::make('discountDate')->sortable(),
            Number::make('discountAmount'),
            Boolean::make('discountApplied')->sortable(),
            Number::make('discountAppliedAmount'),
            DateTime::make('discountAppliedDate'),

            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('contact'),
            BelongsTo::make('salesOrder'),
            BelongsTo::make('journalEntry'),
            HasMany::make('paymentApplications'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('invoice_number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('contact_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('sales_order_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('journal_entry_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_active')->asBoolean(),
            // FI-M002: Discount filters
            \LaravelJsonApi\Eloquent\Filters\Where::make('discount_applied')->asBoolean(),
            \LaravelJsonApi\Eloquent\Filters\Scope::make('withAvailableDiscount', 'with_available_discount'),
            // Paquete A (auditoria 10 pasos): buscador del listado (folio +
            // nombre/email del cliente). El FE ya lo mandaba; sin declararlo, 400.
            \LaravelJsonApi\Eloquent\Filters\Scope::make('search'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'contact',
            'salesOrder',
            'journalEntry',
            'paymentApplications',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "ar-invoices";
    }
}