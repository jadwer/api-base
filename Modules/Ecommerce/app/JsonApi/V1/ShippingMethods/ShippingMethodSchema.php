<?php

namespace Modules\Ecommerce\JsonApi\V1\ShippingMethods;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Ecommerce\Models\ShippingMethod;

class ShippingMethodSchema extends Schema
{
    public static string $model = ShippingMethod::class;

    public function fields(): array
    {
        return [
            ID::make(),

            Str::make('name')->sortable(),
            Str::make('code')->sortable(),
            Str::make('description'),
            Str::make('carrier')->sortable(),

            Number::make('baseCost', 'base_cost')->sortable(),
            Number::make('costPerKg', 'cost_per_kg'),
            Number::make('estimatedDaysMin', 'estimated_days_min')->sortable(),
            Number::make('estimatedDaysMax', 'estimated_days_max')->sortable(),

            Boolean::make('isActive', 'is_active')->sortable(),
            ArrayHash::make('availableCountries', 'available_countries'),
            ArrayHash::make('metadata'),

            Str::make('estimatedDelivery', 'estimated_delivery')->readOnly(),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('isActive', 'is_active'),
            Where::make('carrier'),
            Where::make('code'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
