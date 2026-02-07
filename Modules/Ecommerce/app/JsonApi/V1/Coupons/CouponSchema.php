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
use LaravelJsonApi\Eloquent\Filters\Where;
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
            Number::make('minAmount', 'min_amount'),
            Number::make('maxAmount', 'max_amount'),
            Number::make('maxUses', 'max_uses'),
            Number::make('usedCount', 'used_count'),
            DateTime::make('startsAt', 'starts_at'),
            DateTime::make('expiresAt', 'expires_at'),
            Boolean::make('isActive', 'is_active')->sortable(),
            ArrayList::make('customerIds', 'customer_ids'),
            ArrayList::make('productIds', 'product_ids'),
            ArrayList::make('categoryIds', 'category_ids'),
            DateTime::make("createdAt")->sortable()->readOnly(),
            DateTime::make("updatedAt")->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('isActive', 'is_active'),
            Where::make('code'),
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