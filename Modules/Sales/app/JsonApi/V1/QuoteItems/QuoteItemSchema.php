<?php

namespace Modules\Sales\JsonApi\V1\QuoteItems;

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
use Modules\Sales\Models\QuoteItem;

class QuoteItemSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = QuoteItem::class;

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
            Number::make('quoteId', 'quote_id'),
            Number::make('productId', 'product_id'),

            // Relations
            BelongsTo::make('quote')->type('quotes'),
            BelongsTo::make('product')->type('products'),

            // Quantity and pricing
            Number::make('quantity'),
            Number::make('unitPrice', 'unit_price'), // Original price
            Number::make('quotedPrice', 'quoted_price'), // Quoted price (editable)
            Number::make('discountPercentage', 'discount_percentage'),
            Number::make('discountAmount', 'discount_amount')->readOnly(),
            Number::make('taxRate', 'tax_rate'),
            Number::make('taxAmount', 'tax_amount')->readOnly(),
            Number::make('total')->readOnly(),

            // Product snapshot
            Str::make('productName', 'product_name'),
            Str::make('productSku', 'product_sku'),

            // Notes and metadata
            Str::make('notes'),
            ArrayHash::make('metadata'),

            // Computed fields (read-only)
            Number::make('subtotalBeforeDiscount')->readOnly(),
            Number::make('subtotalAfterDiscount')->readOnly(),
            Number::make('priceVariance')->readOnly(),
            Number::make('effectiveDiscountPercentage')->readOnly(),

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
            Where::make('quote', 'quote_id'),
            Where::make('product', 'product_id'),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'quote',
            'product',
            'product.stock',
            'product.stock.warehouse',
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
        return 'quote-items';
    }
}
