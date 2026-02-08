<?php

namespace Modules\Ecommerce\JsonApi\V1\CartItems;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\CartItem;

class CartItemSchema extends Schema
{
    public static string $model = CartItem::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('shoppingCartId'),
            Str::make('productId'),
            Number::make('quantity'),
            Number::make('unitPrice'),
            Number::make('originalPrice'),
            Number::make('discountPercent'),
            Number::make('discountAmount'),
            Number::make('subtotal'),
            Number::make('taxRate'),
            Number::make('taxAmount'),
            Number::make('total'),
            ArrayHash::make('metadata'),
            Str::make('status'),

            // Relationships
            BelongsTo::make('shoppingCart'),
            BelongsTo::make('product'),
            DateTime::make("createdAt")->sortable()->readOnly(),
            DateTime::make("updatedAt")->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('shopping_cart_id'),
            Where::make('product_id'),
            Where::make('status'),
        ];
    }

    public function includePaths(): iterable
    {
        return [
            'shoppingCart',
            'product',
        ];
    }

    /**
     * Scope index queries to the authenticated user's cart items.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if (!$request || !$request->user('sanctum')) {
            return $query->where('id', 0);
        }

        $user = $request->user('sanctum');

        if ($user->hasAnyRole(['god', 'admin', 'administrator', 'tech'])) {
            return $query;
        }

        return $query->whereHas('shoppingCart', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "cart-items";
    }
}