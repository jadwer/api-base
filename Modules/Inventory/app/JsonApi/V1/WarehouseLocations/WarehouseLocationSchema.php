<?php

namespace Modules\Inventory\JsonApi\V1\WarehouseLocations;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Inventory\Models\WarehouseLocation;

class WarehouseLocationSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = WarehouseLocation::class;

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
            Str::make('code')->sortable(),
            Str::make('description'),
            Str::make('locationType', 'location_type')->sortable(),
            Str::make('aisle'),
            Str::make('rack'),
            Str::make('shelf'),
            Str::make('level'),
            Str::make('position'),
            Str::make('barcode'),
            Number::make('maxWeight', 'max_weight'),
            Number::make('maxVolume', 'max_volume'),
            Str::make('dimensions'),
            Boolean::make('isActive', 'is_active')->sortable(),
            Boolean::make('isPickable', 'is_pickable'),
            Boolean::make('isReceivable', 'is_receivable'),
            Number::make('priority')->sortable(),
            ArrayList::make('metadata')->readOnly(),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            
            // Relaciones
            BelongsTo::make('warehouse', 'warehouse')->type('warehouses'),
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
            Where::make('location_type'),
            Where::make('warehouse_id'),
            Where::make('is_active'),
            Where::make('is_pickable'),
            Where::make('is_receivable'),
            WhereIn::make('location_type'),
        ];
    }

    /**
     * Get the include paths.
     */
    public function includePaths(): array
    {
        return [
            'warehouse',
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
        return 'warehouse-locations';
    }
}
