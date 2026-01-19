<?php

namespace Modules\Sales\JsonApi\V1\RemissionItems;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Sales\Models\RemissionItem;

/**
 * SA-M006: RemissionItem Schema for JSON:API
 */
class RemissionItemSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = RemissionItem::class;

    /**
     * The maximum depth of include paths.
     */
    protected int $maxDepth = 2;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),

            // Foreign keys
            Number::make('remissionId', 'remission_id'),
            Number::make('salesOrderItemId', 'sales_order_item_id'),
            Number::make('productId', 'product_id'),

            // Relations
            BelongsTo::make('remission')->type('remissions'),
            BelongsTo::make('salesOrderItem')->type('sales-order-items'),
            BelongsTo::make('product')->type('products'),

            // Quantity
            Number::make('quantity'),

            // Snapshot data
            Str::make('productName', 'product_name'),
            Str::make('productSku', 'product_sku'),
            Str::make('unit'),
            Str::make('notes'),

            // Batch tracking
            Str::make('batchNumber', 'batch_number'),
            DateTime::make('expiryDate', 'expiry_date'),

            // Metadata
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
            Where::make('remission', 'remission_id'),
            Where::make('product', 'product_id'),
            Where::make('batch_number'),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'remission',
            'salesOrderItem',
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
        return 'remission-items';
    }
}
