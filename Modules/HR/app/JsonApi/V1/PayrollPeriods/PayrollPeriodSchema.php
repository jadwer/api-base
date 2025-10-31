<?php

namespace Modules\HR\JsonApi\V1\PayrollPeriods;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\HR\Models\PayrollPeriod;

class PayrollPeriodSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = PayrollPeriod::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('periodType', 'period_type')->sortable(),
            DateTime::make('startDate', 'start_date')->sortable(),
            DateTime::make('endDate', 'end_date')->sortable(),
            DateTime::make('paymentDate', 'payment_date')->sortable(),
            Str::make('status')->sortable(),
            Number::make('totalGross', 'total_gross')->sortable(),
            Number::make('totalDeductions', 'total_deductions')->sortable(),
            Number::make('totalNet', 'total_net')->sortable(),
            Str::make('notes'),
            HasMany::make('payrollItems')->type('payroll-items'),
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
            Where::make('periodType', 'period_type'),
            Where::make('startDate', 'start_date'),
            Where::make('endDate', 'end_date'),
            Where::make('paymentDate', 'payment_date'),
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
}
