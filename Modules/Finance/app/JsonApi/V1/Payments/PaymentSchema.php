<?php

namespace Modules\Finance\JsonApi\V1\Payments;

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
use Modules\Finance\Models\Payment;

class PaymentSchema extends Schema
{
    public static string $model = Payment::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Str::make('paymentNumber')->sortable(),
            DateTime::make('paymentDate')->sortable(),
            Number::make('contactId')->sortable(),
            Number::make('bankAccountId')->sortable(),
            Number::make('paymentMethodId')->sortable(),
            Number::make('amount')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('appliedAmount')->sortable(),
            Number::make('unappliedAmount')->sortable(),
            Str::make('status')->sortable(),
            Number::make('journalEntryId')->sortable(),
            Str::make('reference')->sortable(),
            Str::make('notes'),
            ArrayHash::make('metadata'),
            Boolean::make('isActive')->sortable(),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('contact'),
            BelongsTo::make('bankAccount'),
            BelongsTo::make('paymentMethod'),
            BelongsTo::make('journalEntry'),
            HasMany::make('paymentApplications'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('payment_number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('contact_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('bank_account_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('payment_method_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('journal_entry_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('reference'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_active'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'contact',
            'bankAccount',
            'paymentMethod',
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
        return "payments";
    }
}