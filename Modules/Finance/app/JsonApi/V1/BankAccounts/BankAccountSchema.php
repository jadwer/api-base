<?php

namespace Modules\Finance\JsonApi\V1\BankAccounts;

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
use Modules\Finance\Models\BankAccount;

class BankAccountSchema extends Schema
{
    public static string $model = BankAccount::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Str::make('accountNumber')->sortable(),
            Str::make('accountName')->sortable(),
            Str::make('bankName')->sortable(),
            Str::make('currency')->sortable(),
            Number::make('glAccountId')->sortable(),
            Number::make('currentBalance')->sortable(),
            Number::make('openingBalance')->sortable(),
            Str::make('status')->sortable(),
            ArrayHash::make('metadata'),
            Boolean::make('isActive')->sortable(),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('account'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('account_number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('account_name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('bank_name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('gl_account_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_active'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'account',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "bank-accounts";
    }
}