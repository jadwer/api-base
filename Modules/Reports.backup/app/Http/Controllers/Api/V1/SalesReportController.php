<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\ManagementReports\SalesReportService;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    private SalesReportService $salesReportService;

    public function __construct(SalesReportService $salesReportService)
    {
        $this->salesReportService = $salesReportService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate Sales by Customer Report
     *
     * GET /api/v1/reports/sales-by-customer?start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $report = $this->salesReportService->generateByCustomer($startDate, $endDate);

        return response()->json([
            'data' => $report,
        ]);
    }

    /**
     * Generate Sales by Product Report
     *
     * GET /api/v1/reports/sales-by-product?start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byProduct(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $report = $this->salesReportService->generateByProduct($startDate, $endDate);

        return response()->json([
            'data' => $report,
        ]);
    }
}
