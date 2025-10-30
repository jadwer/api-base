<?php

namespace Modules\Reports\JsonApi\V1\SalesByProductReports;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

class SalesByProductReportSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * For virtual resources (reports), we use stdClass since there's no database model
     *
     * @var string
     */
    public static string $model = \stdClass::class;

    /**
     * Get the JSON:API resource type.
     *
     * @return string
     */
    public static function type(): string
    {
        return 'sales-by-product-reports';
    }

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Report metadata
            Str::make('asOfDate')->sortable(),
            Str::make('currency')->sortable(),
            Boolean::make('balanced'),

            // Assets section - using ArrayHash for complex structures
            ArrayHash::make('assets'),
            Number::make('totalAssets'),

            // Liabilities section
            ArrayHash::make('liabilities'),
            Number::make('totalLiabilities'),

            // Equity section
            ArrayHash::make('equity'),
            Number::make('totalEquity'),

            // Generation timestamp
            DateTime::make('generatedAt')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('as_of_date', 'asOfDate'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        // Reports typically don't need pagination
        return null;
    }

    /**
     * Get the include paths supported by this resource.
     *
     * @return array
     */
    public function includePaths(): array
    {
        // Balance sheets don't have relationships
        return [];
    }
}
