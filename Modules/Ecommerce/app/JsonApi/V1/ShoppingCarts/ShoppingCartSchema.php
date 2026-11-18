<?php

namespace Modules\Ecommerce\JsonApi\V1\ShoppingCarts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\ShoppingCart;

class ShoppingCartSchema extends Schema
{
    public static string $model = ShoppingCart::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('sessionId'),
            Str::make('userId')->readOnly(),
            Str::make('status')->sortable(),
            DateTime::make('expiresAt')->sortable(),
            Number::make('totalAmount')->sortable(),
            Str::make('currency')->sortable(),
            // Seguridad (inventario 19-11): el cupon, el descuento, el IVA y el envio los fijan
            // el servidor (apply-coupon, remove-coupon y el checkout), nunca el cliente; antes
            // el dueno del carrito podia ponerse su propio descuento por PATCH y el checkout lo
            // usaba en el total del pedido.
            Str::make('couponCode')->readOnly(),
            Number::make('discountAmount')->sortable()->readOnly(),
            Number::make('taxAmount')->sortable()->readOnly(),
            Number::make('shippingAmount')->sortable()->readOnly(),
            Str::make('notes'),
            ArrayHash::make('metadata'),

            // ✅ CAMPOS CALCULADOS (NUEVOS - similar a Finance)
            Number::make('itemsCount')->readOnly(),
            Number::make('subtotalAmount')->readOnly(),
            Number::make('computedTaxAmount')->readOnly(),
            Number::make('finalTotal')->readOnly(),
            Boolean::make('isExpired')->readOnly(),
            Boolean::make('canApplyCoupon')->readOnly(),
            // Dueno del carrito para cotizar desde el panel (junta 2026-11-12, J4)
            Str::make('ownerName')->readOnly(),
            Str::make('ownerEmail')->readOnly(),
            Boolean::make('ownerIsCustomer')->readOnly(),

            // Relationships
            HasMany::make('cartItems'),
            BelongsTo::make('user'),
            DateTime::make("createdAt")->sortable()->readOnly(),
            DateTime::make("updatedAt")->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('session_id'),
            Where::make('user_id'),
            Where::make('currency'),
            Where::make('coupon_code'),
            Scope::make('withItems'),
        ];
    }

    public function includePaths(): iterable
    {
        return [
            'cartItems',
            'cartItems.product',
            'user',
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    /**
     * Scope index queries so non-admin users only see their own carts.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        $user = $request?->user();

        if ($user && !\Modules\User\Support\UserAccess::seesAll($user, static::type() . '.index')) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function type(): string
    {
        return "shopping-carts";
    }
}