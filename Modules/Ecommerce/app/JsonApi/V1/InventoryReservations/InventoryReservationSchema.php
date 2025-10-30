<?php

namespace Modules\Ecommerce\JsonApi\V1\InventoryReservations;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\InventoryReservation;

class InventoryReservationSchema extends Schema
{
    public static string $model = InventoryReservation::class;

    public function fields(): array
    {
        return [
            ID::make(),

            Number::make('checkoutSessionId', 'checkout_session_id'),
            Number::make('stockId', 'stock_id'),
            Number::make('productId', 'product_id'),
            Number::make('warehouseId', 'warehouse_id'),

            Number::make('quantityReserved', 'quantity_reserved'),
            Str::make('status'),

            DateTime::make('expiresAt', 'expires_at'),
            DateTime::make('releasedAt', 'released_at'),
            DateTime::make('fulfilledAt', 'fulfilled_at'),
            Str::make('notes'),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            Boolean::make('isExpired', 'is_expired')->readOnly(),
            Boolean::make('isActive', 'is_active')->readOnly(),
            Number::make('timeRemaining', 'time_remaining')->readOnly(),

            BelongsTo::make('checkoutSession')->type('checkout-sessions')->readOnly(),
            BelongsTo::make('stock')->type('stocks')->readOnly(),
            BelongsTo::make('product')->type('products')->readOnly(),
            BelongsTo::make('warehouse')->type('warehouses')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('checkoutSessionId', 'checkout_session_id'),
            Where::make('productId', 'product_id'),
            Where::make('warehouseId', 'warehouse_id'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
