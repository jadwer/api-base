<?php

namespace Modules\Purchase\JsonApi\V1\BudgetAllocations;

use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use Modules\Purchase\Models\BudgetAllocation;

class BudgetAllocationSchema extends Schema
{
    public static string $model = BudgetAllocation::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('budgetId', 'budget_id'),
            Number::make('purchaseOrderId', 'purchase_order_id'),
            Number::make('allocatedAmount', 'allocated_amount')->sortable(),
            Str::make('status')->sortable(),
            Str::make('notes'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),

            // Relationships
            BelongsTo::make('budget')->type('budgets'),
            BelongsTo::make('purchaseOrder')->type('purchase-orders'),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('budgetId', 'budget_id'),
            Where::make('purchaseOrderId', 'purchase_order_id'),
            Where::make('status', 'status'),
            Scope::make('committed'),
            Scope::make('spent'),
            Scope::make('active'),
        ];
    }

    public function includePaths(): array
    {
        return [
            'budget',
            'purchaseOrder',
        ];
    }

    public function pagination(): PagePagination
    {
        return PagePagination::make();
    }
}
