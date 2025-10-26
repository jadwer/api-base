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
            
            Number::make('companyId', 'company_id'),
            Number::make('accountId', 'account_id'),
            Number::make('fiscalYear', 'fiscal_year')->sortable(),
            Number::make('fiscalMonth', 'fiscal_month')->sortable(),
            Number::make('openingBalance', 'opening_balance')->sortable(),
            Number::make('periodDebits', 'period_debits')->sortable(),
            Number::make('periodCredits', 'period_credits')->sortable(),
            Number::make('closingBalance', 'closing_balance')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('fiscal_year'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('fiscal_month'),
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