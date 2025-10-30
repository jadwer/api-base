<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\FinancialStatements\BalanceSheetService;
use Carbon\Carbon;

class BalanceSheetController extends Controller
{
    private BalanceSheetService $balanceSheetService;

    public function __construct(BalanceSheetService $balanceSheetService)
    {
        $this->balanceSheetService = $balanceSheetService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate Balance Sheet
     *
     * GET /api/v1/reports/balance-sheet?as_of_date=2025-10-28
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

        $balanceSheet = $this->balanceSheetService->generate($asOfDate);

        return response()->json([
            'data' => $balanceSheet,
        ]);
    }

    /**
     * Generate Comparative Balance Sheet
     *
     * GET /api/v1/reports/balance-sheet/comparative?current_date=2025-10-28&prior_date=2025-09-30
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

        $comparative = $this->balanceSheetService->generateComparative($currentDate, $priorDate);

        return response()->json([
            'data' => $comparative,
        ]);
    }
}
