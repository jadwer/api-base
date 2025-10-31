<?php

namespace Modules\HR\JsonApi\V1\Positions;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\HR\Models\Position;

class PositionSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Position::class;

    /**
     * The maximum include path depth.
     */
    protected int $maxDepth = 3;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('title')->sortable(),
            Str::make('description')->sortable(),
            Str::make('level')->sortable(),
            Number::make('minSalary', 'min_salary')->sortable(),
            Number::make('maxSalary', 'max_salary')->sortable(),
            Boolean::make('isActive', 'is_active')->sortable(),

            // Relationships
            BelongsTo::make('department')->type('departments'),
            HasMany::make('employees')->type('employees')->readOnly(),

            // Timestamps
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('title'),
            Where::make('level'),
            Where::make('isActive', 'is_active'),
            Where::make('departmentId', 'department_id'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
