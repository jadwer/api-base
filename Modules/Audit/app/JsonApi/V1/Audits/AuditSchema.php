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
            Str::make('event')->sortable()->readOnly(),
            Number::make('userId', 'causer_id')->sortable()->readOnly(),
            Str::make('auditableType', 'subject_type')->sortable()->readOnly(),
            Number::make('auditableId', 'subject_id')->sortable()->readOnly(),
            Str::make('oldValues', 'properties->old')->readOnly(),
            Str::make('newValues', 'properties->attributes')->readOnly(),
            Str::make('ipAddress', 'properties->ip_address')->readOnly(),
            Str::make('userAgent', 'properties->user_agent')->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // MorphTo::make('causer')->types('users', 'users')->readOnly(),
            // Recuperados del Resource manual retirado (2026-09-30): mismo nombre y valor.
            Str::make('causer')->readOnly()->extractUsing(static fn ($model) => $model->causer),
            Str::make('subject')->readOnly()->extractUsing(static fn ($model) => $model->subject),
        ];
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
