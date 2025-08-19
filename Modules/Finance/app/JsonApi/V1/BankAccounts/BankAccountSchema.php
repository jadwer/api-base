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
            
            Str::make('bankName', 'bank_name')->sortable(),
            Str::make('accountNumber', 'account_number')->sortable(),
            Str::make('clabe')->sortable(),
            Str::make('currency')->sortable(),
            Str::make('accountType', 'account_type')->sortable(),
            Number::make('openingBalance', 'opening_balance')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('bank_name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('account_number'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('clabe'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('account_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
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
        return "bank-accounts";
    }
}