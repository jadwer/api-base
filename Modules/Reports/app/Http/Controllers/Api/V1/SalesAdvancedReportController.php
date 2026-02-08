<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\SalesReports\SalesAdvancedReportService;
use Carbon\Carbon;

/**
 * Phase 13: Advanced Sales Reports Controller
 *
 * Endpoints:
 * - GET /api/v1/reports/sales-by-employee
 * - GET /api/v1/reports/sales-by-batch
 * - GET /api/v1/reports/sales-profitability
 * - GET /api/v1/reports/sales-trend
 */
class SalesAdvancedReportController extends Controller
{
    private SalesAdvancedReportService $reportService;

    public function __construct(SalesAdvancedReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Authorize the request against a specific permission.
     */
    private function authorize(Request $request, string $permission): ?JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'jsonapi' => ['version' => '1.0'],
                'errors' => [['status' => '401', 'title' => 'Unauthenticated']],
            ], 401);
        }

        // God and admin roles have full access
        if ($user->hasAnyRole(['god', 'admin'])) {
            return null;
        }

        if (!$user->hasPermissionTo($permission)) {
            return response()->json([
                'jsonapi' => ['version' => '1.0'],
                'errors' => [['status' => '403', 'title' => 'Forbidden']],
            ], 403);
        }

        return null;
    }

    /**
     * Generate Sales by Employee/Salesperson Report
     *
     * GET /api/v1/reports/sales-by-employee?start_date=2026-01-01&end_date=2026-01-19&employee_id=1
     */
    public function byEmployee(Request $request): JsonResponse
    {
        if ($error = $this->authorize($request, 'reports.sales-advanced.index')) {
            return $error;
        }

        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'employee_id' => 'sometimes|integer|exists:users,id',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $employeeId = $request->input('employee_id');

        $report = $this->reportService->generateByEmployee($startDate, $endDate, $employeeId);

        return response()->json(['data' => $report]);
    }

    /**
     * Generate Sales by Batch/Lot Report
     *
     * GET /api/v1/reports/sales-by-batch?start_date=2026-01-01&end_date=2026-01-19&product_id=1&batch_number=LOT001
     */
    public function byBatch(Request $request): JsonResponse
    {
        if ($error = $this->authorize($request, 'reports.sales-advanced.index')) {
            return $error;
        }

        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'product_id' => 'sometimes|integer|exists:products,id',
            'batch_number' => 'sometimes|string|max:100',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $productId = $request->input('product_id');
        $batchNumber = $request->input('batch_number');

        $report = $this->reportService->generateByBatch($startDate, $endDate, $productId, $batchNumber);

        return response()->json(['data' => $report]);
    }

    /**
     * Generate Sales Profitability Report
     *
     * GET /api/v1/reports/sales-profitability?start_date=2026-01-01&end_date=2026-01-19&category_id=1
     */
    public function profitability(Request $request): JsonResponse
    {
        if ($error = $this->authorize($request, 'reports.sales-advanced.index')) {
            return $error;
        }

        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'category_id' => 'sometimes|integer|exists:categories,id',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $categoryId = $request->input('category_id');

        $report = $this->reportService->generateProfitabilityReport($startDate, $endDate, $categoryId);

        return response()->json(['data' => $report]);
    }

    /**
     * Generate Sales Trend Report
     *
     * GET /api/v1/reports/sales-trend?start_date=2026-01-01&end_date=2026-01-19&group_by=day
     */
    public function trend(Request $request): JsonResponse
    {
        if ($error = $this->authorize($request, 'reports.sales-advanced.index')) {
            return $error;
        }

        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'group_by' => 'sometimes|string|in:day,week,month',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->subDays(30);

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $groupBy = $request->input('group_by', 'day');

        $report = $this->reportService->generateSalesTrend($startDate, $endDate, $groupBy);

        return response()->json(['data' => $report]);
    }
}
