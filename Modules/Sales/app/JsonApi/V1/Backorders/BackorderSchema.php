<?php

namespace Modules\Sales\JsonApi\V1\Backorders;

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
use Modules\Sales\Models\Backorder;

/**
 * SA-M002: JSON:API Schema for Backorder.
 */
class BackorderSchema extends Schema
{
    public static string $model = Backorder::class;

    protected int $maxDepth = 3;

    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign keys
            Number::make('salesOrderId', 'sales_order_id'),
            Number::make('salesOrderItemId', 'sales_order_item_id'),
            Number::make('productId', 'product_id'),
            Number::make('warehouseId', 'warehouse_id'),

            // Basic fields
            Str::make('backorderNumber', 'backorder_number')->sortable(),
            Number::make('originalQuantity', 'original_quantity'),
            Number::make('backorderQuantity', 'backorder_quantity')->sortable(),
            Number::make('fulfilledQuantity', 'fulfilled_quantity'),
            Str::make('status')->sortable(),
            Str::make('priority')->sortable(),

            // Computed
            Number::make('remainingQuantity', 'remaining_quantity')->readOnly(),

            // Dates
            DateTime::make('expectedDate', 'expected_date')->sortable(),
            DateTime::make('promisedDate', 'promised_date')->sortable(),

            // Notes
            Str::make('notes'),
            Str::make('customerNotes', 'customer_notes'),

            // Metadata
            ArrayHash::make('metadata'),

            // Relationships
            BelongsTo::make('salesOrder')->type('sales-orders')->readOnly(),
            BelongsTo::make('salesOrderItem')->type('sales-order-items')->readOnly(),
            BelongsTo::make('product')->type('products')->readOnly(),
            BelongsTo::make('warehouse')->type('warehouses')->readOnly(),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('backorder_number'),
            Where::make('status'),
            Where::make('priority'),
            Where::make('salesOrder', 'sales_order_id'),
            Where::make('salesOrderItem', 'sales_order_item_id'),
            Where::make('product', 'product_id'),
            Where::make('warehouse', 'warehouse_id'),
            Where::make('expected_date'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'salesOrder',
            'salesOrder.contact',
            'salesOrderItem',
            'salesOrderItem.product',
            'product',
            'warehouse',
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'backorders';
    }
}
