<?php

namespace Modules\Finance\JsonApi\V1\BankStatementLines;

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
use Modules\Finance\Models\BankStatementLine;

class BankStatementLineSchema extends Schema
{
    public static string $model = BankStatementLine::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('bankStatementId'),
            DateTime::make('txnDate')->sortable(),
            Number::make('amount')->sortable(),
            Str::make('counterparty')->sortable(),
            Str::make('reference')->sortable(),
            Str::make('fitid')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('counterparty'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('reference'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('fitid'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
        ];
    }

    public function sortables(): array
    {
        return [
            'txn_date',
            'amount',
            'counterparty',
            'reference',
            'fitid',
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
        return "bank-statement-lines";
    }
}