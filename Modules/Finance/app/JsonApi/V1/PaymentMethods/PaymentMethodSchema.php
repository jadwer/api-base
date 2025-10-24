<?php

namespace Modules\Finance\JsonApi\V1\PaymentMethods;

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
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Finance\Models\PaymentMethod;

class PaymentMethodSchema extends Schema
{
    public static string $model = PaymentMethod::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Str::make('code')->sortable(),
            Str::make('name')->sortable(),
            Str::make('type')->sortable(),
            Boolean::make('requiresReference')->sortable(),
            Boolean::make('isActive')->sortable(),
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('code'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('name'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('requires_reference'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_active'),
        ];
    }

    public function includePaths(): array
    {
        return [

        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return "payment-methods";
    }
}