<?php

namespace Modules\Sales\JsonApi\V1\Quotes;

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
use Modules\Sales\Models\Quote;

class QuoteSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Quote::class;

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
            Number::make('contactId', 'contact_id'),
            Number::make('shoppingCartId', 'shopping_cart_id'),
            Number::make('salesOrderId', 'sales_order_id'),
            Number::make('purchaseOrderId', 'purchase_order_id')->readOnly(),

            // Relations
            BelongsTo::make('contact')->type('contacts'),
            BelongsTo::make('shoppingCart')->type('shopping-carts'),
            BelongsTo::make('salesOrder')->type('sales-orders'),
            BelongsTo::make('purchaseOrder')->type('purchase-orders'),

            // Basic fields
            Str::make('quoteNumber', 'quote_number')->sortable(),
            Str::make('status')->sortable(),
            DateTime::make('quoteDate', 'quote_date')->sortable(),
            DateTime::make('validUntil', 'valid_until')->sortable(),
            Str::make('estimatedEta', 'estimated_eta'),

            // Amount fields
            Number::make('subtotalAmount', 'subtotal_amount')->sortable(),
            Number::make('discountAmount', 'discount_amount'),
            Number::make('taxAmount', 'tax_amount'),
            Number::make('totalAmount', 'total_amount')->sortable(),
            Str::make('currency'),

            // Text fields
            Str::make('notes'),
            Str::make('internalNotes', 'internal_notes'),
            Str::make('termsAndConditions', 'terms_and_conditions'),

            // Address JSON
            ArrayHash::make('shippingAddress', 'shipping_address'),
            ArrayHash::make('billingAddress', 'billing_address'),
            ArrayHash::make('metadata'),

            // Status timestamps
            DateTime::make('sentAt', 'sent_at')->readOnly(),
            DateTime::make('acceptedAt', 'accepted_at')->readOnly(),
            DateTime::make('rejectedAt', 'rejected_at')->readOnly(),
            DateTime::make('convertedAt', 'converted_at')->readOnly(),

            // Computed attributes (read-only)
            Number::make('itemsCount')->readOnly(),
            Number::make('totalQuantity')->readOnly(),

            // Items relation
            HasMany::make('items')->type('quote-items'),

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
            Where::make('quote_number'),
            Where::make('status'),
            Where::make('contact', 'contact_id'),
            Where::make('quote_date'),
            Where::make('valid_until'),
            WhereIn::make('status'),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'contact',
            'shoppingCart',
            'salesOrder',
            'purchaseOrder',
            'items',
            'items.product',
            'items.product.stock',
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
        return 'quotes';
    }
}
