<?php

namespace Modules\Inventory\JsonApi\V1\CycleCounts;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Inventory\Models\CycleCount;

class CycleCountSchema extends Schema
{
    public static string $model = CycleCount::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('countNumber'),
            DateTime::make('scheduledDate'),
            DateTime::make('completedDate'),
            Str::make('status'),
            Number::make('systemQuantity'),
            Number::make('countedQuantity'),
            Number::make('varianceQuantity'),
            Number::make('varianceValue'),
            Str::make('abcClass'),
            Str::make('notes'),
            ArrayHash::make('metadata'),
            Boolean::make('hasVariance')->readOnly(),
            Number::make('variancePercentage')->readOnly(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('warehouse')->readOnly(),
            BelongsTo::make('warehouseLocation')->readOnly(),
            BelongsTo::make('product')->readOnly(),
            BelongsTo::make('assignedTo')->type('users')->readOnly(),
            BelongsTo::make('countedBy')->type('users')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            WhereIn::make('status', 'statuses'),
            Where::make('abcClass', 'abc_class'),
            Where::make('warehouse', 'warehouse_id'),
            Where::make('product', 'product_id'),
            Where::make('assignedTo', 'assigned_to'),
        ];
    }

    public function pagination(): PagePagination
    {
        return PagePagination::make();
    }
}
