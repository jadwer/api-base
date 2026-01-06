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
            Str::make('budgetType')->sortable(),
            Str::make('departmentCode'),
            Number::make('categoryId'),
            Str::make('projectCode'),
            Number::make('contactId'),
            Str::make('periodType')->sortable(),
            DateTime::make('startDate')->sortable(),
            DateTime::make('endDate')->sortable(),
            Number::make('fiscalYear')->sortable(),
            Number::make('budgetedAmount')->sortable(),
            Number::make('committedAmount')->sortable(),
            Number::make('spentAmount')->sortable(),
            Number::make('availableAmount')->readOnly(),
            Number::make('warningThreshold'),
            Number::make('criticalThreshold'),
            Boolean::make('hardLimit'),
            Boolean::make('allowOvercommit'),
            Boolean::make('isActive')->sortable(),

            // Computed attributes
            Number::make('utilizationPercent')->readOnly()->extractUsing(
                fn ($model) => $model->utilization_percent
            ),
            Str::make('statusLevel')->readOnly()->extractUsing(
                fn ($model) => $model->status_level
            ),

            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),

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
            Where::make('isActive', 'is_active'),
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
