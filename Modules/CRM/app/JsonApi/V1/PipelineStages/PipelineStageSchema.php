<?php

namespace Modules\CRM\JsonApi\V1\PipelineStages;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\CRM\Models\PipelineStage;

class PipelineStageSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = PipelineStage::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('stageType', 'type')->sortable(),
            Number::make('probability')->sortable(),
            Number::make('sortOrder', 'sort_order')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),
            Boolean::make('isClosedWon', 'is_closed_won')->sortable(),
            Boolean::make('isClosedLost', 'is_closed_lost')->sortable(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('stageType', 'type'),
            Where::make('isActive', 'is_active'),
            Where::make('isClosedWon', 'is_closed_won'),
            Where::make('isClosedLost', 'is_closed_lost'),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
