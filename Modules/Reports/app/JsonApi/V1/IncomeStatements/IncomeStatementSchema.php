<?php

namespace Modules\Reports\JsonApi\V1\IncomeStatements;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

class IncomeStatementSchema extends Schema
{
    public static string $model = \stdClass::class;

    /**
     * Get the JSON:API resource type.
     *
     * @return string
     */
    public static function type(): string
    {
        return 'income-statements';
    }

    public function fields(): array
    {
        return [
            ID::make(),
            ArrayHash::make('period'),
            Str::make('currency')->sortable(),
            ArrayHash::make('revenue'),
            Number::make('costOfGoodsSold'),
            Number::make('grossProfit'),
            Number::make('grossProfitMargin'),
            ArrayHash::make('operatingExpenses'),
            Number::make('operatingIncome'),
            Number::make('operatingMargin'),
            ArrayHash::make('otherIncomeExpenses'),
            Number::make('netIncome'),
            Number::make('netProfitMargin'),
            DateTime::make('generatedAt')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('start_date', 'startDate'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('end_date', 'endDate'),
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
