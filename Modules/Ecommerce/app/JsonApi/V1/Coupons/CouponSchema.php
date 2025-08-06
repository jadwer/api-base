<?php

namespace Modules\Ecommerce\JsonApi\V1\Coupons;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\Coupon;

class CouponSchema extends Schema
{
    public static string $model = Coupon::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('code')->sortable(),
            Str::make('name')->sortable(),
            Str::make('description'),
            Str::make('couponType', 'type')->sortable(),
            Number::make('value'),
            Number::make('minAmount'),
            Number::make('maxAmount'),
            Number::make('maxUses'),
            Number::make('usedCount'),
            DateTime::make('startsAt'),
            DateTime::make('expiresAt'),
            Boolean::make('isActive')->sortable(),
            ArrayList::make('customerIds'),
            ArrayList::make('productIds'),
            ArrayList::make('categoryIds'),
            DateTime::make("createdAt")->sortable()->readOnly(),
            DateTime::make("updatedAt")->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "coupons";
    }
}