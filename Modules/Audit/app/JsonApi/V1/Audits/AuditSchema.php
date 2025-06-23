<?php

namespace Modules\Audit\JsonApi\V1\Audits;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;

use Spatie\Activitylog\Models\Activity;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;

class AuditSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Activity::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
        ID::make()->sortable(),
        Str::make('event')->sortable(),
        Number::make('userId', 'causer_id')->sortable(),
        Str::make('auditableType', 'subject_type')->sortable(),
        Number::make('auditableId', 'subject_id')->sortable(),
        Str::make('oldValues', 'properties->old'),
        Str::make('newValues', 'properties->attributes'),
        Str::make('ipAddress', 'properties->ip_address'),
        Str::make('userAgent', 'properties->user_agent'),
        DateTime::make('createdAt', 'created_at')->sortable(),
        DateTime::make('updatedAt', 'updated_at')->sortable(),

        // Relaciones
        MorphTo::make('causer'),
        MorphTo::make('subject'),
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
            Where::make('causer', 'causer_id'),
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

    public static function type(): string
    {
        return 'audits';
    }
}
