<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\FinancialStatements\CashFlowService;
use Carbon\Carbon;

class CashFlowController extends Controller
{
    private CashFlowService $cashFlowService;

    public function __construct(CashFlowService $cashFlowService)
    {
        $this->cashFlowService = $cashFlowService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate Cash Flow Statement
     *
     * GET /api/v1/reports/cash-flow?start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
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

        $cashFlow = $this->cashFlowService->generate($startDate, $endDate);

        return response()->json([
            'data' => $cashFlow,
        ]);
    }

    /**
     * Generate Comparative Cash Flow Statement
     *
     * GET /api/v1/reports/cash-flow/comparative?current_start=2025-10-01&current_end=2025-10-31&prior_start=2025-09-01&prior_end=2025-09-30
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function comparative(Request $request): JsonResponse
    {
        $request->validate([
            'current_start' => 'required|date',
            'current_end' => 'required|date|after_or_equal:current_start',
            'prior_start' => 'required|date',
            'prior_end' => 'required|date|after_or_equal:prior_start|before:current_start',
        ]);

        $currentStartDate = Carbon::parse($request->input('current_start'));
        $currentEndDate = Carbon::parse($request->input('current_end'));
        $priorStartDate = Carbon::parse($request->input('prior_start'));
        $priorEndDate = Carbon::parse($request->input('prior_end'));

        $comparative = $this->cashFlowService->generateComparative(
            $currentStartDate,
            $currentEndDate,
            $priorStartDate,
            $priorEndDate
        );

        return response()->json([
            'data' => $comparative,
        ]);
    }
}
