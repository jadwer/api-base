<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\FinancialStatements\IncomeStatementService;
use Carbon\Carbon;

class IncomeStatementController extends Controller
{
    private IncomeStatementService $incomeStatementService;

    public function __construct(IncomeStatementService $incomeStatementService)
    {
        $this->incomeStatementService = $incomeStatementService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate Income Statement
     *
     * GET /api/v1/reports/income-statement?start_date=2025-01-01&end_date=2025-10-28
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

        $incomeStatement = $this->incomeStatementService->generate($startDate, $endDate);

        return response()->json([
            'data' => $incomeStatement,
        ]);
    }

    /**
     * Generate Comparative Income Statement
     *
     * GET /api/v1/reports/income-statement/comparative?current_start=2025-10-01&current_end=2025-10-31&prior_start=2025-09-01&prior_end=2025-09-30
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

        $comparative = $this->incomeStatementService->generateComparative(
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
