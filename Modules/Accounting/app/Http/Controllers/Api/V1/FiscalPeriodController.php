<?php

namespace Modules\Accounting\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Services\PeriodCloseService;

class FiscalPeriodController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    protected PeriodCloseService $periodCloseService;

    public function __construct(PeriodCloseService $periodCloseService)
    {
        $this->periodCloseService = $periodCloseService;
    }

    /**
     * AC-M001: Get close checklist for a fiscal period.
     */
    public function closeChecklist(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('fiscal-periods.show')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $checklist = $this->periodCloseService->getCloseChecklist($fiscalPeriod);

        return response()->json([
            'data' => $checklist,
        ]);
    }

    /**
     * AC-M001: Close a fiscal period.
     */
    public function close(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('fiscal-periods.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $force = $request->boolean('force', false);

        $result = $this->periodCloseService->closePeriod($fiscalPeriod, $user, $force);

        return response()->json([
            'data' => $result,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * AC-M001: Reopen a closed fiscal period.
     */
    public function reopen(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('fiscal-periods.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $reason = $request->input('reason');

        $result = $this->periodCloseService->reopenPeriod($fiscalPeriod, $user, $reason);

        return response()->json([
            'data' => $result,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * AC-M001: Get period summary with financial totals.
     */
    public function summary(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('fiscal-periods.show')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $summary = $this->periodCloseService->getPeriodSummary($fiscalPeriod);

        return response()->json([
            'data' => $summary,
        ]);
    }
}
