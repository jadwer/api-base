<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Las rutas solo piden sesion: sin esto cualquier usuario, incluido un
     * cliente del portal, leia los montos presupuestados (SEG-03, 22-11).
     */
    private function denyWithoutBudgetAccess(Request $request): ?JsonResponse
    {
        if ($request->user()?->can('budgets.index')) {
            return null;
        }

        return response()->json([
            'errors' => [['status' => '403', 'title' => 'Forbidden', 'detail' => 'No tiene permisos para consultar presupuestos.']],
        ], 403);
    }

    /**
     * Get budget utilization summary.
     */
    public function summary(Request $request): JsonResponse
    {
        if ($denied = $this->denyWithoutBudgetAccess($request)) {
            return $denied;
        }

        $summary = $this->budgetService->getBudgetSummary();

        return response()->json([
            'data' => $summary,
        ]);
    }

    /**
     * Get budgets that need attention.
     */
    public function needsAttention(Request $request): JsonResponse
    {
        if ($denied = $this->denyWithoutBudgetAccess($request)) {
            return $denied;
        }

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
