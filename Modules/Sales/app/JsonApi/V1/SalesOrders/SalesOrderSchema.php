<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

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
use Modules\Sales\Models\SalesOrder;

class SalesOrderSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = SalesOrder::class;

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
            
            // Foreign key
            Number::make('contact_id'),
            
            // Relación con Contact
            BelongsTo::make('contact')->type('contacts'),
            
            // Relación con Customer (usa contact_id como clave foránea)
            BelongsTo::make('customer')->type('customers'),
            
            // Campos básicos - snake_case consistency
            Str::make('order_number')->sortable(),
            Str::make('status')->sortable(),
            DateTime::make('order_date')->sortable(),
            DateTime::make('approved_at')->sortable(),
            DateTime::make('delivered_at')->sortable(),
            
            // Campos de montos
            Number::make('subtotal_amount')->sortable(),
            Number::make('tax_amount'),
            Number::make('discount_total'),
            Number::make('total_amount')->sortable(),
            
            Str::make('notes'),
            
            // Finance Integration Fields
            Number::make('ar_invoice_id'),
            Str::make('invoicing_status')->sortable(),
            Str::make('invoicing_notes'),
            
            // Metadata JSON
            ArrayHash::make('metadata'),
            
            // Relación con Items
            HasMany::make('items')->type('sales-order-items'),
            
            // Timestamps
            DateTime::make('created_at')->readOnly()->sortable(),
            DateTime::make('updated_at')->readOnly()->sortable(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('order_number'),
            Where::make('status'),
            Where::make('contact', 'contact_id'),
            Where::make('order_date'),
            Where::make('invoicing_status'),
            Where::make('ar_invoice_id'),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'contact',
            'customer', // Alias para contact en contexto de ventas
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
        return 'sales-orders';
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
