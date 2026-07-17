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
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Sales\Models\SalesOrder;
use Modules\Contacts\Models\Contact;

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

            // Foreign keys
            Number::make('contactId', 'contact_id'),
            Number::make('quoteId', 'quote_id')->readOnly(),

            // Relations
            BelongsTo::make('contact')->type('contacts'),
            BelongsTo::make('quote')->type('quotes'),
            
            // Campos básicos - camelCase for JSON:API, mapped to snake_case in DB
            Str::make('orderNumber', 'order_number')->sortable(),
            // Refactor ciclo (Patron 1): el status NO puede cambiarse por PATCH.
            // Un PATCH directo a 'delivered'/'cancelled' saltaba OrderStatusService y
            // evitaba reservar/descontar stock y disparar los eventos de dominio.
            // Escribible solo en creacion (estado inicial); las transiciones van por
            // los endpoints de accion (confirm/deliver/cancel) via OrderStatusService.
            Str::make('status')->sortable()->readOnlyOnUpdate(),
            DateTime::make('orderDate', 'order_date')->sortable(),
            DateTime::make('approvedAt', 'approved_at')->sortable(),
            DateTime::make('deliveredAt', 'delivered_at')->sortable(),

            // Campos de montos
            Number::make('discountTotal', 'discount_total'),
            Number::make('subtotalAmount', 'subtotal')->sortable(),
            Number::make('taxAmount', 'tax_amount'),
            Number::make('totalAmount', 'total_amount')->sortable(),
            
            Str::make('notes'),

            // Fase A - Venta directa vs Pedido
            // order_type se fija al crear/convertir; customer_po_path solo via upload
            Str::make('orderType', 'order_type')->readOnly(),
            Str::make('customerPoNumber', 'customer_po_number'),
            Str::make('customerPoPath', 'customer_po_path')->readOnly(),
            Str::make('paymentMethod', 'payment_method'),
            Number::make('creditDays', 'credit_days'),

            // Currency fields
            Str::make('currency'),
            Number::make('exchangeRateUsed', 'exchange_rate_used'),

            // Finance Integration Fields
            Number::make('arInvoiceId', 'ar_invoice_id'),
            Str::make('invoicingStatus', 'invoicing_status')->sortable(),
            Str::make('invoicingNotes', 'invoicing_notes'),
            
            // Metadata JSON
            ArrayHash::make('metadata'),
            
            // Relación con Items
            HasMany::make('items')->type('sales-order-items'),

            // SA-M001: Relación con Shipments
            HasMany::make('shipments')->type('shipments'),

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
            Where::make('order_number'),
            Where::make('status'),
            Where::make('order_type'),
            Where::make('contact', 'contact_id'),
            Where::make('order_date'),
            Where::make('invoicing_status'),
            Where::make('ar_invoice_id'),
            // Customer Portal filter - filter by contact email
            Scope::make('contact_email', 'forContactEmail'),
            // Nota cliente #11: pedidos "por surtir" (abiertos, no entregados/cerrados)
            Scope::make('pending_fulfillment', 'pendingFulfillment'),
        ];
    }

    /**
     * Get the resource include paths.
     */
    public function includePaths(): array
    {
        return [
            'contact',
            'quote',
            'items',
            'items.product',
            // SA-M001: Shipments
            'shipments',
            'shipments.items',
        ];
    }

    /**
     * Scope index queries: admins see all, customers see own orders only.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if (!$request || !$request->user('sanctum')) {
            return $query->whereRaw('1 = 0');
        }

        $user = $request->user('sanctum');

        if ($user->hasAnyRole(['god', 'admin', 'administrator', 'tech'])) {
            return $query;
        }

        // Customers only see their own orders (via contact email match)
        $contact = Contact::where('email', $user->email)->first();
        if ($contact) {
            return $query->where('contact_id', $contact->id);
        }

        return $query->whereRaw('1 = 0');
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
}
