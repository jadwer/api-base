<?php

namespace Modules\HR\JsonApi\V1\Employees;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
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
use Modules\HR\Models\Employee;

class EmployeeSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Employee::class;

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
            Str::make('employeeCode', 'employee_code')->sortable(),
            Str::make('firstName', 'first_name')->sortable(),
            Str::make('lastName', 'last_name')->sortable(),
            Str::make('email')->sortable(),
            Str::make('phone')->sortable(),
            DateTime::make('hireDate', 'hire_date')->sortable(),
            DateTime::make('birthDate', 'birth_date')->sortable(),
            Number::make('salary')->sortable(),
            Str::make('status')->sortable(),
            DateTime::make('terminationDate', 'termination_date')->sortable(),
            Str::make('terminationReason', 'termination_reason'),
            Str::make('address'),
            Str::make('emergencyContactName', 'emergency_contact_name'),
            Str::make('emergencyContactPhone', 'emergency_contact_phone'),

            // Relationships
            BelongsTo::make('department')->type('departments'),
            BelongsTo::make('position')->type('positions'),
            BelongsTo::make('user')->type('users')->readOnly(),
            HasMany::make('managedDepartments')->type('departments')->readOnly(),
            HasMany::make('attendances')->type('attendances')->readOnly(),
            HasMany::make('leaves')->type('leaves')->readOnly(),
            HasMany::make('payrollItems')->type('payroll-items')->readOnly(),
            HasMany::make('performanceReviews')->type('performance-reviews')->readOnly(),

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
            Where::make('employeeCode', 'employee_code'),
            Where::make('firstName', 'first_name'),
            Where::make('lastName', 'last_name'),
            Where::make('email'),
            Where::make('status'),
            Where::make('departmentId', 'department_id'),
            Where::make('positionId', 'position_id'),
            Where::make('userId', 'user_id'),
        ];
    }

    /**
     * Get the resource paginator.
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
            'department',
            'position',
            'user',
            'managedDepartments',
            'attendances',
            'leaves',
            'payrollItems',
            'performanceReviews',
        ];
    }
}
