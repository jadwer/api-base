<?php

namespace Modules\Accounting\JsonApi\V1\AuditLogs;

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
use Modules\Accounting\Models\AuditLog;

class AuditLogSchema extends Schema
{
    public static string $model = AuditLog::class;

    public function fields(): array
    {
        return [
            ID::make(),            Str::make('modelType')->sortable(),
            Number::make('modelId')->sortable(),
            Str::make('action')->sortable(),
            Number::make('userId', 'user_id'),
            ArrayHash::make('changes'),
            Str::make('ipAddress', 'ip_address')->sortable(),
            Str::make('userAgent', 'user_agent'),
            Str::make('sessionId')->sortable(),
            Str::make('payloadHash')->sortable(),
            Boolean::make('requiresRetention')->sortable(),
            DateTime::make('retentionUntil')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('model_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('model_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('action'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('ipAddress', 'ip_address'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('session_id'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('payload_hash'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('requires_retention'),
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
        return "audit-logs";
    }
}