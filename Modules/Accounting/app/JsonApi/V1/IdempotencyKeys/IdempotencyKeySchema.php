<?php

namespace Modules\Accounting\JsonApi\V1\IdempotencyKeys;

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
use Modules\Accounting\Models\IdempotencyKey;

class IdempotencyKeySchema extends Schema
{
    public static string $model = IdempotencyKey::class;

    public function fields(): array
    {
        return [
            ID::make(),            Number::make('userId', 'user_id'),
            Str::make('endpoint')->sortable(),
            Str::make('idempotencyKey', 'idempotency_key')->sortable(),
            Str::make('requestHash', 'request_hash')->sortable(),
            ArrayHash::make('responseData', 'response_data'),
            Str::make('status')->sortable(),
            DateTime::make('expiresAt', 'expires_at')->sortable(),
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
            \LaravelJsonApi\Eloquent\Filters\Where::make('endpoint'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('idempotencyKey', 'idempotency_key'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('requestHash', 'request_hash'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('status'),
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
        return "idempotency-keys";
    }
}