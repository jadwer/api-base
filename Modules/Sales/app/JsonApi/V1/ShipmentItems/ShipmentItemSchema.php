<?php

namespace Modules\Sales\JsonApi\V1\ShipmentItems;

use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Sales\Models\ShipmentItem;

/**
 * SA-M001: JSON:API Schema for ShipmentItem.
 */
class ShipmentItemSchema extends Schema
{
    public static string $model = ShipmentItem::class;

    protected int $maxDepth = 3;

    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign keys
            Number::make('shipmentId', 'shipment_id'),
            Number::make('salesOrderItemId', 'sales_order_item_id'),

            // Basic fields
            Number::make('quantity')->sortable(),
            Str::make('notes'),

            // Metadata
            ArrayHash::make('metadata'),

            // Relationships
            BelongsTo::make('shipment')->type('shipments')->readOnly(),
            BelongsTo::make('salesOrderItem')->type('sales-order-items')->readOnly(),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('shipment', 'shipment_id'),
            Where::make('salesOrderItem', 'sales_order_item_id'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'shipment',
            'shipment.salesOrder',
            'salesOrderItem',
            'salesOrderItem.product',
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'shipment-items';
    }
}
