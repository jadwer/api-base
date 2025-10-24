<?php

namespace Modules\Accounting\JsonApi\V1\AccountBalances;

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
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceSchema extends Schema
{
    public static string $model = AccountBalance::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('companyId'),
            Number::make('accountId'),
            Number::make('fiscalYear')->sortable(),
            Number::make('fiscalMonth')->sortable(),
            Number::make('openingBalance')->sortable(),
            Number::make('periodDebits')->sortable(),
            Number::make('periodCredits')->sortable(),
            Number::make('closingBalance')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('fiscal_year'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('fiscal_month'),
        ];
    }

    public function sortables(): array
    {
        return [
            'fiscal_year',
            'fiscal_month',
            'opening_balance',
            'period_debits',
            'period_credits',
            'closing_balance',
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
        return "account-balances";
    }
}