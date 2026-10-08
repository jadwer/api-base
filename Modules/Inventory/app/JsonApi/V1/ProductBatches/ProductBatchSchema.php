<?php

namespace Modules\Inventory\JsonApi\V1\ProductBatches;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Inventory\Models\ProductBatch;

class ProductBatchSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = ProductBatch::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            
            // Identificadores del lote
            Str::make('batchNumber', 'batch_number')
                ->sortable(),
            Str::make('lotNumber', 'lot_number')
                ->sortable(),
            
            // Fechas importantes
            // Columnas date: salen como Y-m-d (en UTC a medianoche el frontend las pintaba un dia antes)
            DateTime::make('manufacturingDate', 'manufacturing_date')->serializeUsing(static fn ($value) => $value?->format('Y-m-d'))
                ->sortable(),
            DateTime::make('expirationDate', 'expiration_date')->serializeUsing(static fn ($value) => $value?->format('Y-m-d'))
                ->sortable(),
            DateTime::make('bestBeforeDate', 'best_before_date')->serializeUsing(static fn ($value) => $value?->format('Y-m-d'))
                ->sortable(),
            
            // Cantidades
            Number::make('initialQuantity', 'initial_quantity')
                ->sortable(),
            Number::make('currentQuantity', 'current_quantity')
                ->sortable(),
            Number::make('reservedQuantity', 'reserved_quantity')
                ->sortable(),
            Number::make('availableQuantity', 'available_quantity')
                ->sortable()
                ->readOnly(),
            
            // Costos y valores
            Number::make('unitCost', 'unit_cost')
                ->sortable(),
            Number::make('totalValue', 'total_value')
                ->sortable()
                ->readOnly(),
            
            // Estado y proveedor
            Str::make('status')
                ->sortable(),
            Str::make('supplierName', 'supplier_name'),
            Str::make('supplierBatch', 'supplier_batch'),
            
            // Información de calidad
            Str::make('qualityNotes', 'quality_notes'),
            ArrayHash::make('testResults', 'test_results'),
            ArrayHash::make('certifications'),
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
            BelongsTo::make('warehouseLocation')
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
            Where::make('batch_number'),
            Where::make('lot_number'),
            Where::make('product_id'),
            Where::make('warehouse_id'),
            Where::make('warehouse_location_id'),
            // Buscador del listado (numero de lote, lote del proveedor,
            // proveedor, nombre/SKU del producto). Agrupado en el scope para
            // no fugar el OR sobre los demas filtros.
            Scope::make('search'),
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
            'warehouseLocation',
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
        return 'product-batches';
    }
}
