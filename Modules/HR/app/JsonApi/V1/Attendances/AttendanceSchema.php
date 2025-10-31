<?php

namespace Modules\HR\JsonApi\V1\Attendances;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\HR\Models\Attendance;

class AttendanceSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Attendance::class;

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
            DateTime::make('date')->sortable(),
            Str::make('checkIn', 'check_in')->sortable(),
            Str::make('checkOut', 'check_out')->sortable(),
            Number::make('hoursWorked', 'hours_worked')->sortable(),
            Number::make('overtimeHours', 'overtime_hours')->sortable(),
            Str::make('status')->sortable(),
            Str::make('notes'),

            // Relationships
            BelongsTo::make('employee')->type('employees'),

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
            Where::make('date'),
            Where::make('status'),
            Where::make('employee', 'employee_id'),
            Where::make('employeeId', 'employee_id'),
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
