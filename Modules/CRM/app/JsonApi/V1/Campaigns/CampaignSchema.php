<?php

namespace Modules\CRM\JsonApi\V1\Campaigns;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\CRM\Models\Campaign;

class CampaignSchema extends Schema
{
    public static string $model = Campaign::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('type')->sortable(),
            Str::make('status')->sortable(),
            Str::make('startDate', 'start_date')->sortable(),
            Str::make('endDate', 'end_date')->sortable(),
            Number::make('budget')->sortable(),
            Number::make('actualCost', 'actual_cost')->sortable(),
            Number::make('expectedRevenue', 'expected_revenue')->sortable(),
            Number::make('actualRevenue', 'actual_revenue')->sortable(),
            Str::make('targetAudience', 'target_audience'),
            Str::make('description'),
            ArrayHash::make('metadata'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('user')
                ->type('users')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            BelongsToMany::make('leads')
                ->type('leads')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            Where::make('name'),
            Where::make('type'),
            Where::make('status'),
            Where::make('userId', 'user_id'),
            Where::make('startDate', 'start_date'),
            Where::make('endDate', 'end_date'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
