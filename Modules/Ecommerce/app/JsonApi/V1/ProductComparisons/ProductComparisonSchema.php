<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisons;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\ProductComparison;

class ProductComparisonSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = ProductComparison::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Boolean::make('isPublic', 'is_public')->sortable(),
            BelongsTo::make('user')->type('users')->readOnly(),
            HasMany::make('items', 'items')->type('product-comparison-items'),
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
            Where::make('userId', 'user_id'),
            Where::make('isPublic', 'is_public'),
        ];
    }

    public function includePaths(): iterable
    {
        return [
            'user',
            'items',
            'items.product',
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
