<?php

namespace Modules\Accounting\JsonApi\V1\Accounts;

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
use Modules\Accounting\Models\Account;

class AccountSchema extends Schema
{
    public static string $model = Account::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('companyId'),
            Str::make('code')->sortable(),
            Str::make('name')->sortable(),
            Str::make('accountType')->sortable(),
            Str::make('nature')->sortable(),
            Number::make('level')->sortable(),
            Number::make('parentId'),
            Str::make('currency')->sortable(),
            Boolean::make('isPostable')->sortable(),
            Boolean::make('isCashFlow')->sortable(),
            Str::make('status')->sortable(),
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            HasMany::make('accounts'),
            // Relationships
            BelongsTo::make('account'),
            // Relationships
            HasMany::make('journalLines'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('code'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('account_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('nature'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('level'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_postable'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_cash_flow'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
        ];
    }

    public function sortables(): array
    {
        return [
            'code',
            'name',
            'accountType',
            'nature',
            'level',
            'currency',
            'isPostable',
            'isCashFlow',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    public function includePaths(): array
    {
        return [
            'accounts',
            'account',
            'journalLines',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "accounts";
    }
}