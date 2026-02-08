<?php

namespace Modules\HR\JsonApi\V1\Leaves;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\HR\Models\Leave;

class LeaveSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Leave::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            DateTime::make('startDate', 'start_date')->sortable(),
            DateTime::make('endDate', 'end_date')->sortable(),
            Number::make('daysRequested', 'days_requested')->sortable(),
            Str::make('status')->sortable(),
            Str::make('reason'),
            Str::make('notes'),
            DateTime::make('approvedAt', 'approved_at')->sortable(),
            BelongsTo::make('employee')->type('employees'),
            BelongsTo::make('leaveType')->type('leave-types'),
            BelongsTo::make('approver')->type('employees'),
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
            Where::make('status'),
            Where::make('employeeId', 'employee_id'),
            Where::make('leaveTypeId', 'leave_type_id'),
            Where::make('startDate', 'start_date'),
            Where::make('endDate', 'end_date'),
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

    /**
     * Get the include paths supported by this resource.
     */
    public function includePaths(): iterable
    {
        return [
            'employee',
            'leaveType',
            'approver',
        ];
    }
}
