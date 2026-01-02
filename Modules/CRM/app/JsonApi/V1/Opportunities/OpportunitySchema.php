<?php

namespace Modules\CRM\JsonApi\V1\Opportunities;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\CRM\Models\Opportunity;

class OpportunitySchema extends Schema
{
    public static string $model = Opportunity::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('description'),

            // Financial fields
            Number::make('amount')->sortable(),
            Number::make('probability')->sortable(),
            Number::make('expectedRevenue', 'expected_revenue')->sortable()->readOnly(),
            Number::make('actualRevenue', 'actual_revenue')->sortable(),

            // Dates
            Str::make('closeDate', 'close_date')->sortable(),

            // Status and pipeline
            Str::make('status')->sortable(),
            Str::make('stage')->sortable(),
            Str::make('forecastCategory', 'forecast_category')->sortable(),

            // Additional fields
            Str::make('source'),
            Str::make('nextStep', 'next_step'),
            Str::make('lossReason', 'loss_reason'),

            // Win/Loss tracking
            DateTime::make('wonAt', 'won_at')->sortable(),
            DateTime::make('lostAt', 'lost_at')->sortable(),

            // Metadata
            ArrayHash::make('metadata'),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('lead')
                ->type('leads')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            BelongsTo::make('user')
                ->type('users')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            BelongsTo::make('pipelineStage')
                ->type('pipeline-stages')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            BelongsTo::make('contact')
                ->type('contacts')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            HasMany::make('activities')
                ->type('activities')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            Where::make('name'),
            Where::make('status'),
            Where::make('stage'),
            Where::make('forecastCategory', 'forecast_category'),
            Where::make('source'),
            Where::make('userId', 'user_id'),
            Where::make('leadId', 'lead_id'),
            Where::make('pipelineStageId', 'pipeline_stage_id'),
            Where::make('contactId', 'contact_id'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'opportunities';
    }
}
