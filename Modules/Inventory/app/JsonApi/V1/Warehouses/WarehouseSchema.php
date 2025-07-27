<?php

namespace Modules\Inventory\JsonApi\V1\Warehouses;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Inventory\Models\Warehouse;

class WarehouseSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Warehouse::class;

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
            
            // Campos básicos
            Str::make('name')->sortable(),
            Str::make('slug')->sortable(),
            Str::make('description'),
            Str::make('code')->sortable(),
            Str::make('warehouseType', 'warehouse_type')->sortable(),
            Str::make('address'),
            Str::make('city')->sortable(),
            Str::make('state'),
            Str::make('country'),
            Str::make('postalCode', 'postal_code'),
            Str::make('phone'),
            Str::make('email'),
            Str::make('managerName', 'manager_name'),
            Number::make('maxCapacity', 'max_capacity'),
            Str::make('capacityUnit', 'capacity_unit'),
            Str::make('operatingHours', 'operating_hours')->readOnly(),
            Str::make('metadata')->readOnly(),
            Boolean::make('isActive', 'is_active')->sortable(),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            
            // Relaciones
            HasMany::make('locations', 'locations')->type('warehouse-locations'),
            HasMany::make('stock', 'stock')->type('stocks'),
            HasMany::make('productBatches', 'productBatches')->type('product-batches'),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('code'),
            Where::make('warehouse_type'),
            Where::make('is_active'),
            WhereIn::make('warehouse_type'),
        ];
    }

    /**
     * Get the include paths.
     */
    public function includePaths(): array
    {
        return [
            'locations',
            'stock',
            'productBatches',
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
     * Get the resource type.
     */
    public static function type(): string
    {
        return 'warehouses';
    }
}
