<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrderItems;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use Modules\Purchase\Models\PurchaseOrderItem;

class PurchaseOrderItemSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = PurchaseOrderItem::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('quantity')
                ->sortable(),
            Number::make('unitPrice', 'unit_price')
                ->sortable(),
            Number::make('subtotal')
                ->sortable(),
            
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            
            // Relaciones
            BelongsTo::make('purchaseOrder')
                     ->type('purchase-orders'),
            BelongsTo::make('product')
                     ->type('products'),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('purchaseOrder', 'purchase_order_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('productId', 'product_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('quantity'),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'purchaseOrder',
            'product',
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
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
}
