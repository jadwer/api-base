<?php

namespace Modules\Sales\JsonApi\V1\SalesOrderItems;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Sales\Models\SalesOrderItem;

class SalesOrderItemSchema extends Schema
{
    public static string $model = SalesOrderItem::class;

    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('salesOrder'),
            BelongsTo::make('product'),
            Number::make('quantity'),
            Number::make('unit_price'),
            Number::make('discount'),
            Number::make('total'),
            ArrayHash::make('metadata'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('sales_order_id'),
            Where::make('product_id'),
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }
}
