<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisonItems;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\ProductComparisonItem;

class ProductComparisonItemSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = ProductComparisonItem::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign key IDs (for creation/updates)
            Number::make('comparisonId', 'comparison_id'),
            Number::make('productId', 'product_id'),

            Number::make('position')->sortable(),

            // Relationships (for includes only)
            BelongsTo::make('comparison')->type('product-comparisons')->readOnly(),
            BelongsTo::make('product')->type('products')->readOnly(),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('comparisonId', 'comparison_id'),
            Where::make('productId', 'product_id'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
