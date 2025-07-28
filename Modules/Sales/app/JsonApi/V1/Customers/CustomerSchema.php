<?php

namespace Modules\Sales\JsonApi\V1\Customers;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Sales\Models\Customer;

class CustomerSchema extends Schema
{
    public static string $model = Customer::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name'),
            Str::make('email'),
            Str::make('phone'),
            Str::make('address'),
            Str::make('city'),
            Str::make('state'),
            Str::make('country'),
            Str::make('classification'),
            Number::make('credit_limit'),
            Number::make('current_credit'),
            Boolean::make('is_active'),
            ArrayHash::make('metadata'),
            DateTime::make('created_at'),
            DateTime::make('updated_at'),
            HasMany::make('salesOrders'),
        ];
    }

    public function filters(): array
    {
        return [
            Where::make('name'),
            Where::make('email'),
            Where::make('classification'),
            Where::make('is_active'),
        ];
    }

    public function pagination(): ?PagePagination
    {
        return PagePagination::make();
    }
}
