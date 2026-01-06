<?php

namespace Modules\Finance\JsonApi\V1\BankTransactions;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Finance\Models\BankTransaction;

class BankTransactionSchema extends Schema
{
    public static string $model = BankTransaction::class;

    public function fields(): array
    {
        return [
            ID::make(),

            // Core fields
            Number::make('bankAccountId')->sortable(),
            DateTime::make('transactionDate')->sortable(),
            Number::make('amount')->sortable(),
            Str::make('transactionType')->sortable(),
            Str::make('reference')->sortable(),
            Str::make('description'),

            // Reconciliation fields
            Str::make('reconciliationStatus')->sortable(),
            Number::make('reconciledById'),
            DateTime::make('reconciledAt')->sortable(),
            Str::make('reconciliationNotes'),

            // Bank statement info
            Str::make('statementNumber')->sortable(),
            Number::make('runningBalance'),

            // Status
            Boolean::make('isActive')->sortable(),

            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('bankAccount'),
            BelongsTo::make('reconciledBy'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('bank_account_id'),
            Where::make('transaction_type'),
            Where::make('reconciliation_status'),
            Where::make('statement_number'),
            Where::make('reference'),
            Where::make('is_active'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'bankAccount',
            'reconciledBy',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "bank-transactions";
    }
}
