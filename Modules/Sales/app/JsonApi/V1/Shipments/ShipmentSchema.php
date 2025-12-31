<?php

namespace Modules\Sales\JsonApi\V1\Shipments;

use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Sales\Models\Shipment;

/**
 * SA-M001: JSON:API Schema for Shipment.
 */
class ShipmentSchema extends Schema
{
    public static string $model = Shipment::class;

    protected int $maxDepth = 3;

    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign keys
            Number::make('salesOrderId', 'sales_order_id'),
            Number::make('warehouseId', 'warehouse_id'),

            // Basic fields
            Str::make('shipmentNumber', 'shipment_number')->sortable(),
            Str::make('status')->sortable(),
            Str::make('carrier')->sortable(),
            Str::make('trackingNumber', 'tracking_number'),
            Str::make('trackingUrl', 'tracking_url'),

            // Dates
            DateTime::make('shipDate', 'ship_date')->sortable(),
            DateTime::make('estimatedDelivery', 'estimated_delivery')->sortable(),
            DateTime::make('actualDelivery', 'actual_delivery')->sortable(),

            // Address and notes
            Str::make('shippingAddress', 'shipping_address'),
            Str::make('notes'),

            // Amounts
            Number::make('shippingCost', 'shipping_cost'),
            Number::make('weight'),

            // Computed attributes
            Number::make('totalItems', 'total_items')->readOnly(),
            Number::make('totalQuantity', 'total_quantity')->readOnly(),

            // Metadata
            ArrayHash::make('metadata'),

            // Relationships
            BelongsTo::make('salesOrder')->type('sales-orders')->readOnly(),
            BelongsTo::make('warehouse')->type('warehouses')->readOnly(),
            HasMany::make('items')->type('shipment-items'),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('shipment_number'),
            Where::make('status'),
            Where::make('carrier'),
            Where::make('tracking_number'),
            Where::make('salesOrder', 'sales_order_id'),
            Where::make('warehouse', 'warehouse_id'),
            Where::make('ship_date'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'salesOrder',
            'salesOrder.contact',
            'warehouse',
            'items',
            'items.salesOrderItem',
            'items.salesOrderItem.product',
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'shipments';
    }
}
