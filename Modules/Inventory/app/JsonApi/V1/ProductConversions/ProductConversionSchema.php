<?php

namespace Modules\Inventory\JsonApi\V1\ProductConversions;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Inventory\Models\ProductConversion;

class ProductConversionSchema extends Schema
{
    public static string $model = ProductConversion::class;

    protected int $maxDepth = 3;

    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign keys
            Number::make('sourceProductId', 'source_product_id'),
            Number::make('destinationProductId', 'destination_product_id'),

            // Conversion data
            Number::make('conversionFactor', 'conversion_factor')->sortable(),
            Number::make('wastePercentage', 'waste_percentage')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),
            Str::make('notes'),

            // Relationships
            BelongsTo::make('sourceProduct')->type('products'),
            BelongsTo::make('destinationProduct')->type('products'),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('sourceProduct', 'source_product_id'),
            Where::make('destinationProduct', 'destination_product_id'),
            Where::make('isActive', 'is_active'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'sourceProduct',
            'destinationProduct',
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'product-conversions';
    }

    public function relationships(): array
    {
        return [];
    }
}
