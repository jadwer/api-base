<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\ManagementReports\PurchaseReportService;
use Carbon\Carbon;

class PurchaseReportController extends Controller
{
    private PurchaseReportService $purchaseReportService;

    public function __construct(PurchaseReportService $purchaseReportService)
    {
        $this->purchaseReportService = $purchaseReportService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate Purchase by Supplier Report
     *
     * GET /api/v1/reports/purchase-by-supplier?start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bySupplier(Request $request): JsonResponse
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

        $report = $this->purchaseReportService->generateBySupplier($startDate, $endDate);

        return response()->json([
            'data' => $report,
        ]);
    }

    /**
     * Generate Purchase by Product Report
     *
     * GET /api/v1/reports/purchase-by-product?start_date=2025-01-01&end_date=2025-10-28
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

        $report = $this->purchaseReportService->generateByProduct($startDate, $endDate);

        return response()->json([
            'data' => $report,
        ]);
    }
}
