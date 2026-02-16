<?php

namespace Modules\Inventory\JsonApi\V1\Fractionations;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Inventory\Models\Fractionation;

class FractionationSchema extends Schema
{
    public static string $model = Fractionation::class;

    protected int $maxDepth = 3;

    public function fields(): array
    {
        return [
            ID::make(),

            // Folio
            Str::make('folioNumber', 'folio_number')->sortable(),

            // Foreign keys
            Number::make('sourceProductId', 'source_product_id'),
            Number::make('destinationProductId', 'destination_product_id'),
            Number::make('productConversionId', 'product_conversion_id'),
            Number::make('warehouseId', 'warehouse_id'),
            Number::make('userId', 'user_id'),

            // Quantities
            Number::make('sourceQuantity', 'source_quantity')->sortable(),
            Number::make('producedQuantity', 'produced_quantity')->sortable(),
            Number::make('wastePercentage', 'waste_percentage'),
            Number::make('wasteQuantity', 'waste_quantity'),
            Number::make('conversionFactorUsed', 'conversion_factor_used'),

            // Movement IDs
            Number::make('exitMovementId', 'exit_movement_id'),
            Number::make('entryMovementId', 'entry_movement_id'),

            // Status
            Str::make('status')->sortable(),
            Str::make('notes'),
            DateTime::make('executedAt', 'executed_at')->sortable(),

            // Relationships
            BelongsTo::make('sourceProduct')->type('products'),
            BelongsTo::make('destinationProduct')->type('products'),
            BelongsTo::make('productConversion')->type('product-conversions'),
            BelongsTo::make('warehouse')->type('warehouses'),
            BelongsTo::make('user')->type('users'),
            BelongsTo::make('exitMovement')->type('inventory-movements'),
            BelongsTo::make('entryMovement')->type('inventory-movements'),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('folioNumber', 'folio_number'),
            Where::make('sourceProduct', 'source_product_id'),
            Where::make('destinationProduct', 'destination_product_id'),
            Where::make('warehouse', 'warehouse_id'),
            Where::make('user', 'user_id'),
            Where::make('status'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'sourceProduct',
            'destinationProduct',
            'productConversion',
            'warehouse',
            'user',
            'exitMovement',
            'entryMovement',
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'fractionations';
    }

    public function relationships(): array
    {
        return [];
    }
}
