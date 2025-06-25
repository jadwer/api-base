<?php

namespace Modules\Audit\JsonApi\V1\Audits;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Audit\Models\Audit;

class AuditSchema extends Schema
{
    public static string $model = Audit::class;

    public static function type(): string
    {
        return 'audits';
    }

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

            // MorphTo::make('causer')->types('users', 'users')->readOnly(),
        ];
    }

    public function relationships(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
        Where::make('causer', 'causer_id'),
        Where::make('event'),
        Where::make('auditableType', 'subject_type'),
        Where::make('auditableId', 'subject_id'),
        ];
    }

    public function includePaths(): array
    {
        return [
            //            'causer'
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
