<?php

namespace Modules\Branch\JsonApi\V1\Branches;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\Branch\Models\Branch;

class BranchSchema extends Schema
{
    public static string $model = Branch::class;

    public static function type(): string
    {
        return 'branches';
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('code')->sortable(),
            Str::make('address'),
            Str::make('city')->sortable(),
            Str::make('state'),
            Str::make('postalCode', 'postal_code'),
            Str::make('phone'),
            Str::make('email'),
            Boolean::make('isActive', 'is_active')->sortable(),
            Boolean::make('isMain', 'is_main'),
            DateTime::make('createdAt', 'created_at')->readOnly()->sortable(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
            Where::make('code'),
            Where::make('city'),
            Where::make('state'),
            Where::make('isActive', 'is_active')->asBoolean(),
            Where::make('isMain', 'is_main')->asBoolean(),
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
}
