<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRatePolicies;

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
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicySchema extends Schema
{
    public static string $model = ExchangeRatePolicy::class;

    public function fields(): array
    {
        return [
            ID::make(),            Str::make('currency')->sortable(),
            Str::make('source')->sortable(),
            Str::make('scope')->sortable(),
            Number::make('maxAgeDays')->sortable(),
            Number::make('tolerancePercentage')->sortable(),
            Number::make('requireApprovalOver')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('currency'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('source'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('scope'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('max_age_days'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('isActive', 'is_active')->asBoolean(),
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
        return "exchange-rate-policies";
    }
}