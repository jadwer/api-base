<?php

namespace Modules\HR\JsonApi\V1\PayrollItems;

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
use Modules\HR\Models\PayrollItem;

class PayrollItemSchema extends Schema
{
    public static string $model = PayrollItem::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('basicSalary', 'basic_salary')->sortable(),
            Number::make('overtimePay', 'overtime_pay')->sortable(),
            Number::make('bonuses')->sortable(),
            Number::make('deductions')->sortable(),
            Number::make('netPay', 'net_pay')->sortable(),
            Str::make('status')->sortable(),
            DateTime::make('paidAt', 'paid_at')->sortable(),
            Str::make('notes'),
            BelongsTo::make('employee')->type('employees'),
            BelongsTo::make('payrollPeriod')->type('payroll-periods'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('employeeId', 'employee_id'),
            Where::make('payrollPeriodId', 'payroll_period_id'),
        ];
    }

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
            'payrollPeriod',
        ];
    }
}
