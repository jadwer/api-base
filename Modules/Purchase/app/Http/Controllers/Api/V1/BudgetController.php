<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Purchase\Services\BudgetControlService;

class BudgetController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;

    protected BudgetControlService $budgetService;

    public function __construct(BudgetControlService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    /**
     * Get budget utilization summary.
     */
    public function summary(): JsonResponse
    {
        $summary = $this->budgetService->getBudgetSummary();

        return response()->json([
            'data' => $summary,
        ]);
    }

    /**
     * Get budgets that need attention.
     */
    public function needsAttention(): JsonResponse
    {
        $budgets = $this->budgetService->getBudgetsNeedingAttention();

        return response()->json([
            'data' => $budgets->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'budgeted_amount' => $b->budgeted_amount,
                'utilization_percent' => $b->utilization_percent,
                'status_level' => $b->status_level,
                'available_amount' => $b->available_amount,
            ]),
        ]);
    }
}
