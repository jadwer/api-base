<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrders;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Scope;
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
            Str::make('orderNumber', 'order_number'),
            Number::make('contactId', 'contact_id'),
            Number::make('warehouseId', 'warehouse_id'),
            DateTime::make('orderDate', 'order_date')
                ->sortable(),
            Str::make('status')
                ->sortable(),
            Number::make('totalAmount', 'total_amount')
                ->sortable(),
            Str::make('notes'),

            // Approval workflow fields
            Str::make('approvalStatus', 'approval_status')->sortable(),
            DateTime::make('approvedAt', 'approved_at')->sortable()->readOnly(),
            Number::make('approvedById', 'approved_by_id')->readOnly(),
            Str::make('financialStatus', 'financial_status')->sortable(),

            // Finance integration fields
            Number::make('apInvoiceId', 'ap_invoice_id')->sortable(),
            Str::make('invoicingStatus', 'invoicing_status')->sortable(),
            Str::make('invoicingNotes', 'invoicing_notes'),

            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

            BelongsTo::make('contact')->type('contacts'),
            BelongsTo::make('warehouse')->type('warehouses'),
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
            Where::make('contact', 'contact_id'),
            Where::make('warehouse', 'warehouse_id'),
            // Nota cliente #11: compras "por surtir" (status pending+approved)
            Scope::make('pending_receipt', 'pendingReceipt'),
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
            'contact',
            'warehouse',
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
