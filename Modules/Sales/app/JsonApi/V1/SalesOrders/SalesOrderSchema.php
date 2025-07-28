<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Sales\Models\SalesOrder;

class SalesOrderSchema extends Schema
{
    public static string $model = SalesOrder::class;

    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('customer'),
            Str::make('order_number'),
            Str::make('status'),
            DateTime::make('order_date'),
            DateTime::make('approved_at'),
            DateTime::make('delivered_at'),
            Number::make('total_amount'),
            Number::make('discount_total'),
            Str::make('notes'),
            ArrayHash::make('metadata'),
            DateTime::make('created_at'),
            DateTime::make('updated_at'),
            HasMany::make('items'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('order_number'),
            Where::make('status'),
            Where::make('customer_id'),
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }
}
