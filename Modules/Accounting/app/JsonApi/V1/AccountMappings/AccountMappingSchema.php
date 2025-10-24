<?php

namespace Modules\Accounting\JsonApi\V1\AccountMappings;

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
use Modules\Accounting\Models\AccountMapping;

class AccountMappingSchema extends Schema
{
    public static string $model = AccountMapping::class;

    public function fields(): array
    {
        return [
            ID::make(),
            
            Number::make('companyId'),
            Str::make('mappingType')->sortable(),
            Number::make('accountId'),
            Number::make('version')->sortable(),
            DateTime::make('effectiveFrom')->sortable(),
            DateTime::make('effectiveTo')->sortable(),
            Boolean::make('isActive')->sortable(),
            Number::make('createdById'),
            Str::make('notes'),
            // Metadata
            ArrayHash::make('metadata'),
            
            // Timestamps
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            \LaravelJsonApi\Eloquent\Filters\Where::make('mapping_type'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('version'),
            \LaravelJsonApi\Eloquent\Filters\Where::make('is_active'),
        ];
    }

    public function sortables(): array
    {
        return [
            'mappingType',
            'version',
            'effectiveFrom',
            'effectiveTo',
            'isActive',
            'created_at',
            'updated_at',
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
        return "account-mappings";
    }
}