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
            Str::make('idempotencyKey', 'idempotency_key')->readOnly(),

            // Foreign keys
            Number::make('sourceProductId', 'source_product_id')->acceptStrings(),
            Number::make('destinationProductId', 'destination_product_id')->acceptStrings(),
            Number::make('productConversionId', 'product_conversion_id')->acceptStrings(),
            Number::make('warehouseId', 'warehouse_id')->acceptStrings(),
            Number::make('warehouseLocationId', 'warehouse_location_id')->acceptStrings()->readOnly(),
            Number::make('sourceBatchId', 'source_batch_id')->acceptStrings()->readOnly(),
            Number::make('destinationBatchId', 'destination_batch_id')->acceptStrings()->readOnly(),
            Number::make('userId', 'user_id')->acceptStrings(),

            // Quantities
            Number::make('sourceQuantity', 'source_quantity')->sortable(),
            Number::make('requestedQuantity', 'requested_quantity')->readOnly(),
            Number::make('producedQuantity', 'produced_quantity')->sortable(),
            Number::make('wastePercentage', 'waste_percentage'),
            Number::make('wasteQuantity', 'waste_quantity'),
            Number::make('conversionFactorUsed', 'conversion_factor_used'),

            // Costos (P3 granel: la merma se absorbe en el costo destino)
            Number::make('sourceUnitCost', 'source_unit_cost')->readOnly(),
            Number::make('destinationUnitCost', 'destination_unit_cost')->readOnly(),
            Number::make('totalCost', 'total_cost')->readOnly(),

            // Movement IDs
            Number::make('exitMovementId', 'exit_movement_id')->acceptStrings(),
            Number::make('entryMovementId', 'entry_movement_id')->acceptStrings(),

            // Status
            Str::make('status')->sortable(),
            Str::make('notes'),
            DateTime::make('executedAt', 'executed_at')->sortable(),
            // Cancelacion (G10, 23-10): solo la escribe el endpoint cancel
            DateTime::make('cancelledAt', 'cancelled_at')->readOnly(),
            Number::make('cancelledBy', 'cancelled_by')->readOnly(),
            Str::make('cancellationReason', 'cancellation_reason')->readOnly(),

            // Relationships
            BelongsTo::make('sourceProduct')->type('products'),
            BelongsTo::make('destinationProduct')->type('products'),
            BelongsTo::make('productConversion')->type('product-conversions'),
            BelongsTo::make('warehouse')->type('warehouses'),
            BelongsTo::make('warehouseLocation')->type('warehouse-locations')->readOnly(),
            BelongsTo::make('sourceBatch')->type('product-batches')->readOnly(),
            BelongsTo::make('destinationBatch')->type('product-batches')->readOnly(),
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
            Where::make('sourceBatch', 'source_batch_id'),
            Where::make('destinationBatch', 'destination_batch_id'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'sourceProduct',
            'destinationProduct',
            'productConversion',
            'warehouse',
            'warehouseLocation',
            'sourceBatch',
            'destinationBatch',
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
}
