<?php

namespace Modules\Inventory\JsonApi\V1\Stocks;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Inventory\app\Models\Stock;

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
            ArrayList::make('batchInfo', 'batch_info'),
            ArrayList::make('metadata'),
            
            // Fechas del sistema
            DateTime::make('createdAt')
                ->sortable()
                ->readOnly(),
            DateTime::make('updatedAt')
                ->sortable()
                ->readOnly(),
            
            // Relaciones
            BelongsTo::make('product')
                ->type('products')
                ->readOnly(),
            BelongsTo::make('warehouse')
                ->type('warehouses')
                ->readOnly(),
            BelongsTo::make('location')
                ->type('warehouse-locations')
                ->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
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
