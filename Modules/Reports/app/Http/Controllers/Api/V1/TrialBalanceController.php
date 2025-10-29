<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\FinancialStatements\TrialBalanceService;
use Carbon\Carbon;

class TrialBalanceController extends Controller
{
    private TrialBalanceService $trialBalanceService;

    public function __construct(TrialBalanceService $trialBalanceService)
    {
        $this->trialBalanceService = $trialBalanceService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate Trial Balance
     *
     * GET /api/v1/reports/trial-balance?as_of_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'sometimes|date',
        ]);

        $asOfDate = $request->has('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $trialBalance = $this->trialBalanceService->generate($asOfDate);

        return response()->json([
            'data' => $trialBalance,
        ]);
    }

    /**
     * Generate Comparative Trial Balance
     *
     * GET /api/v1/reports/trial-balance/comparative?current_date=2025-10-28&prior_date=2025-09-30
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function comparative(Request $request): JsonResponse
    {
        $request->validate([
            'current_date' => 'required|date',
            'prior_date' => 'required|date|before:current_date',
        ]);

        $currentDate = Carbon::parse($request->input('current_date'));
        $priorDate = Carbon::parse($request->input('prior_date'));

        $comparative = $this->trialBalanceService->generateComparative($currentDate, $priorDate);

        return response()->json([
            'data' => $comparative,
        ]);
    }

    /**
     * Generate Detailed Trial Balance with Period Activity
     *
     * GET /api/v1/reports/trial-balance/detailed?start_date=2025-10-01&end_date=2025-10-31
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detailed(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        // Default to current month if no dates provided
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $detailedTrialBalance = $this->trialBalanceService->generateDetailed($startDate, $endDate);

        return response()->json([
            'data' => $detailedTrialBalance,
        ]);
    }
}
