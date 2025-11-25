<?php

namespace Modules\CRM\JsonApi\V1\Activities;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\CRM\Models\Activity;

class ActivitySchema extends Schema
{
    public static string $model = Activity::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('type')->sortable(),
            Str::make('subject')->sortable(),
            Str::make('description'),
            DateTime::make('activityDate', 'activity_date')->sortable(),
            Number::make('duration')->sortable(),
            Str::make('outcome'),
            Str::make('status')->sortable(),
            ArrayHash::make('metadata'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('user')
                ->type('users')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            BelongsTo::make('lead')
                ->type('leads')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            BelongsTo::make('campaign')
                ->type('campaigns')
                ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),

            // BelongsTo::make('contact')
            //     ->type('contacts')
            //     ->serializeUsing(static fn ($relation) => $relation->withoutLinks()),
            // TODO: Enable when Contact module is implemented
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('type'),
            Where::make('subject'),
            Where::make('status'),
            Where::make('userId', 'user_id'),
            Where::make('leadId', 'lead_id'),
            Where::make('campaignId', 'campaign_id'),
            // Where::make('contactId', 'contact_id'), // TODO: Enable when Contact module is implemented
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    public static function type(): string
    {
        return 'activities';
    }
}
