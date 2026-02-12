<?php

namespace Modules\Sales\JsonApi\V1\DiscountRules;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleSchema extends Schema
{
    public static string $model = DiscountRule::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('code')->sortable(),
            Str::make('description'),
            Str::make('discountType')->sortable(),
            Number::make('discountValue')->sortable(),
            Number::make('buyQuantity'),
            Number::make('getQuantity'),
            Str::make('appliesTo')->sortable(),
            Number::make('minOrderAmount'),
            Number::make('minQuantity'),
            Number::make('maxDiscountAmount'),
            ArrayHash::make('productIds'),
            ArrayHash::make('categoryIds'),
            ArrayHash::make('customerIds'),
            ArrayHash::make('customerClassifications'),
            DateTime::make('startDate')->sortable(),
            DateTime::make('endDate')->sortable(),
            Number::make('usageLimit'),
            Number::make('usagePerCustomer'),
            Number::make('currentUsage')->readOnly(),
            Number::make('priority')->sortable(),
            Boolean::make('isCombinable'),
            Boolean::make('isActive')->sortable(),

            // Computed attributes
            Boolean::make('isValid')->readOnly()->extractUsing(
                fn ($model) => $model->is_valid
            ),
            Boolean::make('isExpired')->readOnly()->extractUsing(
                fn ($model) => $model->is_expired
            ),
            Number::make('usageRemaining')->readOnly()->extractUsing(
                fn ($model) => $model->usage_remaining
            ),

            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('discountType', 'discount_type'),
            Where::make('appliesTo', 'applies_to'),
            Where::make('isActive', 'is_active')->asBoolean(),
            Where::make('isCombinable', 'is_combinable')->asBoolean(),
            Where::make('code'),
            Scope::make('search', 'forSearch'),
        ];
    }

    public function pagination(): PagePagination
    {
        return PagePagination::make();
    }
}
