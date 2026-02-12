<?php

namespace Modules\Purchase\JsonApi\V1\Budgets;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Purchase\Models\Budget;

class BudgetSchema extends Schema
{
    public static string $model = Budget::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('code')->sortable(),
            Str::make('description'),
            Str::make('budgetType', 'budget_type')->sortable(),
            Str::make('departmentCode', 'department_code'),
            Number::make('categoryId', 'category_id'),
            Str::make('projectCode', 'project_code'),
            Number::make('contactId', 'contact_id'),
            Str::make('periodType', 'period_type')->sortable(),
            DateTime::make('startDate', 'start_date')->sortable(),
            DateTime::make('endDate', 'end_date')->sortable(),
            Number::make('fiscalYear', 'fiscal_year')->sortable(),
            Number::make('budgetedAmount', 'budgeted_amount')->sortable(),
            Number::make('committedAmount', 'committed_amount')->sortable(),
            Number::make('spentAmount', 'spent_amount')->sortable(),
            Number::make('availableAmount', 'available_amount')->readOnly(),
            Number::make('warningThreshold', 'warning_threshold'),
            Number::make('criticalThreshold', 'critical_threshold'),
            Boolean::make('hardLimit', 'hard_limit'),
            Boolean::make('allowOvercommit', 'allow_overcommit'),
            Boolean::make('isActive', 'is_active')->sortable(),

            // Computed attributes
            Number::make('utilizationPercent', 'utilization_percent')->readOnly()->extractUsing(
                fn ($model) => $model->utilization_percent
            ),
            Str::make('statusLevel', 'status_level')->readOnly()->extractUsing(
                fn ($model) => $model->status_level
            ),

            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('category'),
            BelongsTo::make('contact'),
            HasMany::make('allocations'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('budgetType', 'budget_type'),
            Where::make('periodType', 'period_type'),
            Where::make('departmentCode', 'department_code'),
            Where::make('categoryId', 'category_id'),
            Where::make('contactId', 'contact_id'),
            Where::make('fiscalYear', 'fiscal_year'),
            Where::make('isActive', 'is_active')->asBoolean(),
            Scope::make('current'),
            Scope::make('overWarning', 'over_warning'),
            Scope::make('overCritical', 'over_critical'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'category',
            'contact',
            'allocations',
            'allocations.purchaseOrder',
        ];
    }

    public function pagination(): PagePagination
    {
        return PagePagination::make();
    }
}
