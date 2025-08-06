<?php

namespace Modules\Ecommerce\JsonApi\V1\ShoppingCarts;

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
            DateTime::make('expiresAt'),
            Number::make('totalAmount'),
            Str::make('currency'),
            Str::make('couponCode'),
            Number::make('discountAmount'),
            Number::make('taxAmount'),
            Number::make('shippingAmount'),
            Str::make('notes'),
            ArrayHash::make('metadata'),

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
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "shopping-carts";
    }
}