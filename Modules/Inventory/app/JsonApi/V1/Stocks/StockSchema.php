<?php

namespace Modules\Inventory\JsonApi\V1\Stocks;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Inventory\Models\Stock;

class StockSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Stock::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            
            // Cantidades
            Number::make('quantity')
                ->sortable(),
            Number::make('reservedQuantity', 'reserved_quantity')
                ->sortable(),
            Number::make('availableQuantity', 'available_quantity')
                ->sortable()
                ->readOnly(),
            
            // Niveles de stock
            Number::make('minimumStock', 'minimum_stock')
                ->sortable(),
            Number::make('maximumStock', 'maximum_stock')
                ->sortable(),
            Number::make('reorderPoint', 'reorder_point')
                ->sortable(),
            
            // Costos y valores
            Number::make('unitCost', 'unit_cost')
                ->sortable(),
            Number::make('totalValue', 'total_value')
                ->sortable()
                ->readOnly(),
            
            // Estado y movimientos
            Str::make('status')
                ->sortable(),
            DateTime::make('lastMovementDate', 'last_movement_date')
                ->sortable(),
            Str::make('lastMovementType', 'last_movement_type'),
            
            // Información adicional
            ArrayHash::make('batchInfo', 'batch_info'),
            ArrayHash::make('metadata'),
            
            // Fechas del sistema
            DateTime::make('createdAt')
                ->sortable()
                ->readOnly(),
            DateTime::make('updatedAt')
                ->sortable()
                ->readOnly(),
            
            // Relaciones
            BelongsTo::make('product')
                ->type('products'),
            BelongsTo::make('warehouse')
                ->type('warehouses'),
            BelongsTo::make('location')
                ->type('warehouse-locations'),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('product_id'),
            Where::make('warehouse_id'),
            Where::make('warehouse_location_id'),
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
