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
            Str::make('userId'),
            Str::make('status')->sortable(),
            DateTime::make('expiresAt')->sortable(),
            Number::make('totalAmount')->sortable(),
            Str::make('currency')->sortable(),
            Str::make('couponCode'),
            Number::make('discountAmount')->sortable(),
            Number::make('taxAmount')->sortable(),
            Number::make('shippingAmount')->sortable(),
            Str::make('notes'),
            ArrayHash::make('metadata'),

            // ✅ CAMPOS CALCULADOS (NUEVOS - similar a Finance)
            Number::make('itemsCount')->readOnly(),
            Number::make('subtotalAmount')->readOnly(),
            Number::make('finalTotal')->readOnly(),
            Boolean::make('isExpired')->readOnly(),
            Boolean::make('canApplyCoupon')->readOnly(),

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

        if ($user && !$user->hasAnyRole(['god', 'admin', 'tech'])) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function type(): string
    {
        return "shopping-carts";
    }
}