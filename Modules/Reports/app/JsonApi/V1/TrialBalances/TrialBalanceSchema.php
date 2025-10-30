<?php

namespace Modules\Reports\JsonApi\V1\TrialBalances;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

class TrialBalanceSchema extends Schema
{
    public static string $model = \stdClass::class;

    /**
     * Get the JSON:API resource type.
     *
     * @return string
     */
    public static function type(): string
    {
        return 'trial-balances';
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('asOfDate')->sortable(),
            Str::make('currency')->sortable(),
            ArrayHash::make('accounts'),
            ArrayHash::make('totals'),
            ArrayHash::make('summaryByType'),
            Boolean::make('balanced'),
            DateTime::make('generatedAt')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('as_of_date', 'asOfDate'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return null;
    }

    public function includePaths(): array
    {
        return [];
    }
}
