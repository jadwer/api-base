<?php

namespace Modules\Finance\JsonApi\V1\ARPayments;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Finance\Models\ARPayment;

class ARPaymentSchema extends Schema
{
    public static string $model = ARPayment::class;

    /**
     * The resource type as it appears in URIs.
     */
    public static function type(): string
    {
        return 'ar-payments';
    }

    protected int $maxDepth = 3;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('paymentNumber', 'payment_number')->sortable(),
            DateTime::make('paymentDate', 'payment_date')->sortable(),
            Number::make('contactId', 'contact_id'),
            Number::make('fiscalPeriodId', 'fiscal_period_id'),
            Number::make('bankAccountId', 'bank_account_id'),
            Str::make('paymentMethod', 'payment_method')->sortable(),
            Str::make('currency'),
            Number::make('paymentAmount', 'payment_amount')->sortable(),
            Number::make('appliedAmount', 'applied_amount')->sortable(),
            Number::make('unappliedAmount', 'unapplied_amount')->sortable(),
            Str::make('status')->sortable(),
            Str::make('reference'),
            Str::make('notes'),
            Number::make('journalEntryId', 'journal_entry_id'),
            DateTime::make('voidedAt', 'voided_at'),
            Number::make('voidedById', 'voided_by_id'),
            Str::make('voidReason', 'void_reason'),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('contact')->type('contacts'),
            BelongsTo::make('fiscalPeriod')->type('fiscal-periods'),
            BelongsTo::make('bankAccount')->type('bank-accounts'),
            BelongsTo::make('journalEntry')->type('journal-entries'),
            HasMany::make('applications')->type('payment-applications')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('contactId', 'contact_id'),
            Where::make('status'),
            Where::make('paymentMethod', 'payment_method'),
            Where::make('fiscalPeriodId', 'fiscal_period_id'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
