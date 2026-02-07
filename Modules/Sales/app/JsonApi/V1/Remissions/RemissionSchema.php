<?php

namespace Modules\Sales\JsonApi\V1\Remissions;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Sales\Models\Remission;

/**
 * SA-M006: Remission Schema for JSON:API
 */
class RemissionSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Remission::class;

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

            // Foreign keys
            Number::make('salesOrderId', 'sales_order_id'),
            Number::make('shipmentId', 'shipment_id'),
            Number::make('warehouseId', 'warehouse_id'),

            // Relations
            BelongsTo::make('salesOrder')->type('sales-orders'),
            BelongsTo::make('shipment')->type('shipments'),
            BelongsTo::make('warehouse')->type('warehouses'),

            // Basic fields
            Str::make('remissionNumber', 'remission_number')->sortable(),
            Str::make('status')->sortable(),
            DateTime::make('remissionDate', 'remission_date')->sortable(),
            DateTime::make('deliveryDate', 'delivery_date'),

            // Delivery information
            Str::make('deliveredBy', 'delivered_by'),
            Str::make('receivedBy', 'received_by'),
            Str::make('deliveryNotes', 'delivery_notes'),

            // Address JSON
            ArrayHash::make('shippingAddress', 'shipping_address'),

            // PDF
            Str::make('pdfPath', 'pdf_path')->readOnly(),
            DateTime::make('pdfGeneratedAt', 'pdf_generated_at')->readOnly(),

            // Notes
            Str::make('internalNotes', 'internal_notes'),
            ArrayHash::make('metadata'),

            // Computed attributes (read-only)
            Number::make('totalItems')->readOnly(),
            Number::make('totalQuantity')->readOnly(),

            // Items relation
            HasMany::make('items')->type('remission-items'),

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
            Where::make('remission_number'),
            Where::make('salesOrder', 'sales_order_id'),
            Where::make('warehouse', 'warehouse_id'),
            Where::make('remission_date'),
            WhereIn::make('status'),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'salesOrder',
            'salesOrder.contact',
            'shipment',
            'warehouse',
            'items',
            'items.product',
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
        return 'remissions';
    }
}
