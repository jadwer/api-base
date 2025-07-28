<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrders;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use Modules\Purchase\Models\PurchaseOrder;

class PurchaseOrderSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = PurchaseOrder::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('supplierId', 'supplier_id')
                ->sortable(),
            DateTime::make('orderDate', 'order_date')
                ->sortable(),
            Str::make('status')
                ->sortable(),
            Number::make('totalAmount', 'total_amount')
                ->sortable(),
            Str::make('notes'),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            
            BelongsTo::make('supplier')
                ->type('suppliers'),
            HasMany::make('purchaseOrderItems')
                ->type('purchase-order-items'),
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
            Where::make('supplier', 'supplier_id'),
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
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'supplier',
            'purchaseOrderItems',
        ];
    }

    /**
     * Get the JSON:API resource type.
     */
    public static function type(): string
    {
        return 'purchase-orders';
    }
}
