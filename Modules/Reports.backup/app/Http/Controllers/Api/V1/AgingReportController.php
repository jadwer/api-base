<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\ManagementReports\AgingReportService;
use Carbon\Carbon;

class AgingReportController extends Controller
{
    private AgingReportService $agingReportService;

    public function __construct(AgingReportService $agingReportService)
    {
        $this->agingReportService = $agingReportService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate Accounts Receivable Aging Report
     *
     * GET /api/v1/reports/aging-ar?as_of_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ar(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'sometimes|date',
        ]);

        $asOfDate = $request->has('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $agingReport = $this->agingReportService->generateAR($asOfDate);

        return response()->json([
            'data' => $agingReport,
        ]);
    }

    /**
     * Generate Accounts Payable Aging Report
     *
     * GET /api/v1/reports/aging-ap?as_of_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ap(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'sometimes|date',
        ]);

        $asOfDate = $request->has('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $agingReport = $this->agingReportService->generateAP($asOfDate);

        return response()->json([
            'data' => $agingReport,
        ]);
    }
}
