<?php

namespace Modules\Inventory\JsonApi\V1\InventoryMovements;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Inventory\Models\InventoryMovement;

class InventoryMovementSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = InventoryMovement::class;

    /**
     * The maximum depth of include paths.
     */
    protected int $maxDepth = 3;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            
            // Foreign keys (requeridos para crear/editar)
            Number::make('productId', 'product_id'),
            Number::make('warehouseId', 'warehouse_id'),
            Number::make('locationId', 'warehouse_location_id'),
            Number::make('destinationWarehouseId', 'destination_warehouse_id'),
            Number::make('destinationLocationId', 'destination_location_id'),
            Number::make('userId', 'user_id'),
            
            // Campos básicos del movimiento
            Str::make('movementType', 'movement_type')->sortable(),
            Str::make('referenceType', 'reference_type')->sortable(),
            Number::make('referenceId', 'reference_id'),
            DateTime::make('movementDate', 'movement_date')->sortable(),
            Str::make('description'),
            
            // Cantidades y costos
            Number::make('quantity')->sortable(),
            Number::make('unitCost', 'unit_cost')->sortable(),
            Number::make('totalValue', 'total_value')->sortable(),
            
            // Estado y auditoría
            Str::make('status')->sortable(),
            Number::make('previousStock', 'previous_stock'),
            Number::make('newStock', 'new_stock'),
            
            // Campos JSON
            ArrayHash::make('batchInfo', 'batch_info'),
            ArrayHash::make('metadata'),
            
            // Relaciones BelongsTo
            BelongsTo::make('product')
                     ->type('products'),
                     
            BelongsTo::make('warehouse')
                     ->type('warehouses'),
                     
            BelongsTo::make('location')
                     ->type('warehouse-locations'),
                     
            BelongsTo::make('destinationWarehouse')
                     ->type('warehouses'),
                     
            BelongsTo::make('destinationLocation')
                     ->type('warehouse-locations'),
                     
            BelongsTo::make('user')
                     ->type('users'),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('movementType', 'movement_type'),
            Where::make('referenceType', 'reference_type'),
            Where::make('referenceId', 'reference_id'),
            WhereIn::make('product', 'product_id'),
            WhereIn::make('warehouse', 'warehouse_id'),
            WhereIn::make('destinationWarehouse', 'destination_warehouse_id'),
            Where::make('status'),
            WhereIn::make('user', 'user_id'),
            Where::make('movementDate', 'movement_date'),
            Where::make('dateFrom', 'movement_date')->using('>='),
            Where::make('dateTo', 'movement_date')->using('<='),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'product',
            'warehouse',
            'location',
            'destinationWarehouse',
            'destinationLocation',
            'user',
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    /**
     * Get the JSON:API resource type.
     */
    public static function type(): string
    {
        return 'inventory-movements';
    }

    /**
     * Get the resource relationships.
     * ⚠️ CRÍTICO: Mantener vacío - las relaciones van en fields()
     */
    public function relationships(): array
    {
        return [
            //
        ];
    }
}