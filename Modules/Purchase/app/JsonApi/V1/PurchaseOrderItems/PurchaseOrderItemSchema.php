<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrderItems;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Purchase\Models\PurchaseOrderItem;

class PurchaseOrderItemSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = PurchaseOrderItem::class;

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
            
            // Direct foreign key fields (for direct access)
            Number::make('purchaseOrderId', 'purchase_order_id')->sortable(),
            Number::make('productId', 'product_id')->sortable(),
            
            // BelongsTo relationships (for JSON API includes)
            BelongsTo::make('purchaseOrder')->type('purchase-orders'),
            BelongsTo::make('product')->type('products'),
            
            // Numeric fields
            Number::make('quantity')->sortable(),
            Number::make('unitPrice', 'unit_price')->sortable(),
            Number::make('discount')->sortable(),
            Number::make('subtotal')->sortable(),
            Number::make('total')->sortable(),
            
            // JSON fields
            ArrayHash::make('metadata'),
            
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
            Where::make('purchaseOrderId', 'purchase_order_id'),
            Where::make('productId', 'product_id'),
            Where::make('quantity'),
            Where::make('unitPrice', 'unit_price'),
            Where::make('discount'),
            Where::make('subtotal'),
            Where::make('total'),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'purchaseOrder',
            'purchaseOrder.supplier',
            'product',
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
        return 'purchase-order-items';
    }

    /**
     * Get the resource relationships.
     */
    public function relationships(): array
    {
        return [
            //
        ];
    }
}
