<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Modules\Reports\Services\Export\ExportService;
use Modules\Reports\Services\FinancialStatements\BalanceSheetService;
use Modules\Reports\Services\FinancialStatements\IncomeStatementService;
use Modules\Reports\Services\FinancialStatements\CashFlowService;
use Modules\Reports\Services\FinancialStatements\TrialBalanceService;
use Modules\Reports\Services\ManagementReports\AgingReportService;
use Modules\Reports\Services\ManagementReports\SalesReportService;
use Modules\Reports\Services\SalesReports\SalesAdvancedReportService;
use Modules\Reports\Services\SalesReports\SalesHistoryReportService;
use Carbon\Carbon;

class ExportController extends Controller
{
    private ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
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
     * Export Balance Sheet
     *
     * GET /api/v1/reports/balance-sheet/export?format=csv&as_of_date=2025-10-28
     *
     * @param Request $request
     * @param BalanceSheetService $service
     * @return Response
     */
    public function balanceSheet(Request $request, BalanceSheetService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.balance-sheets.index')) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
            'as_of_date' => 'sometimes|date',
        ]);

        $asOfDate = $request->has('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $data = $service->generate($asOfDate);

        return $this->handleExport('balance-sheet', $data, $request->input('format'));
    }

    /**
     * Export Income Statement
     *
     * GET /api/v1/reports/income-statement/export?format=csv&start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @param IncomeStatementService $service
     * @return Response
     */
    public function incomeStatement(Request $request, IncomeStatementService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.income-statements.index')) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $data = $service->generate($startDate, $endDate);

        return $this->handleExport('income-statement', $data, $request->input('format'));
    }

    /**
     * Export Cash Flow Statement
     *
     * GET /api/v1/reports/cash-flow/export?format=csv&start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @param CashFlowService $service
     * @return Response
     */
    public function cashFlow(Request $request, CashFlowService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.cash-flows.index')) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $data = $service->generate($startDate, $endDate);

        return $this->handleExport('cash-flow', $data, $request->input('format'));
    }

    /**
     * Export Trial Balance
     *
     * GET /api/v1/reports/trial-balance/export?format=csv&as_of_date=2025-10-28
     *
     * @param Request $request
     * @param TrialBalanceService $service
     * @return Response
     */
    public function trialBalance(Request $request, TrialBalanceService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.trial-balances.index')) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
            'as_of_date' => 'sometimes|date',
        ]);

        $asOfDate = $request->has('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $data = $service->generate($asOfDate);

        return $this->handleExport('trial-balance', $data, $request->input('format'));
    }

    /**
     * Export Aging Report
     *
     * GET /api/v1/reports/aging-ar/export?format=csv&as_of_date=2025-10-28
     *
     * @param Request $request
     * @param AgingReportService $service
     * @param string $type (ar or ap)
     * @return Response
     */
    public function aging(Request $request, AgingReportService $service, string $type): Response
    {
        $permission = $type === 'ar' ? 'reports.ar-aging-reports.index' : 'reports.ap-aging-reports.index';
        if ($error = $this->authorize($request, $permission)) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
            'as_of_date' => 'sometimes|date',
        ]);

        $asOfDate = $request->has('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $data = $type === 'ar'
            ? $service->generateAR($asOfDate)
            : $service->generateAP($asOfDate);

        return $this->handleExport("aging-{$type}", $data, $request->input('format'));
    }

    /**
     * Export Sales by Customer
     *
     * GET /api/v1/reports/sales-by-customer/export?format=csv&start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @param SalesReportService $service
     * @return Response
     */
    public function salesByCustomer(Request $request, SalesReportService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.sales-by-customer-reports.index')) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $data = $service->generateByCustomer($startDate, $endDate);

        return $this->handleExport('sales-by-customer', $data, $request->input('format'));
    }

    /**
     * Phase 13: Export Sales by Employee
     *
     * GET /api/v1/reports/sales-by-employee/export?format=csv&start_date=2026-01-01&end_date=2026-01-19
     *
     * @param Request $request
     * @param SalesAdvancedReportService $service
     * @return Response
     */
    public function salesByEmployee(Request $request, SalesAdvancedReportService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.sales-advanced.index')) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
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

        $data = $service->generateByEmployee($startDate, $endDate, $employeeId);

        return $this->handleExport('sales-by-employee', $data, $request->input('format'));
    }

    /**
     * Phase 13: Export Sales by Batch
     *
     * GET /api/v1/reports/sales-by-batch/export?format=csv&start_date=2026-01-01&end_date=2026-01-19
     *
     * @param Request $request
     * @param SalesAdvancedReportService $service
     * @return Response
     */
    public function salesByBatch(Request $request, SalesAdvancedReportService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.sales-advanced.index')) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
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

        $data = $service->generateByBatch($startDate, $endDate, $productId, $batchNumber);

        return $this->handleExport('sales-by-batch', $data, $request->input('format'));
    }

    /**
     * Phase 13: Export Sales Profitability Report
     *
     * GET /api/v1/reports/sales-profitability/export?format=csv&start_date=2026-01-01&end_date=2026-01-19
     *
     * @param Request $request
     * @param SalesAdvancedReportService $service
     * @return Response
     */
    public function salesProfitability(Request $request, SalesAdvancedReportService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.sales-advanced.index')) {
            return $error;
        }

        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
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

        $data = $service->generateProfitabilityReport($startDate, $endDate, $categoryId);

        return $this->handleExport('sales-profitability', $data, $request->input('format'));
    }

    /**
     * Export Sales History Report (Historico de Ventas v1)
     *
     * GET /api/v1/reports/sales-history/export?format=csv&start_date=2026-07-01&end_date=2026-07-09
     *
     * Accepts the same filters as GET /api/v1/reports/sales-history
     * (contact_id, assigned_to, product_id, category_id, brand_id, status CSV,
     * currency, iva, order_number, group_by). Exports the FULL filtered set,
     * without pagination.
     *
     * @param Request $request
     * @param SalesHistoryReportService $service
     * @return Response
     */
    public function salesHistory(Request $request, SalesHistoryReportService $service): Response
    {
        if ($error = $this->authorize($request, 'reports.sales-history.index')) {
            return $error;
        }

        $request->validate(array_merge(
            SalesHistoryReportController::filterRules(),
            ['format' => 'required|in:csv,pdf,excel']
        ));

        $statuses = SalesHistoryReportController::parseStatuses($request);

        [$startDate, $endDate] = SalesHistoryReportController::resolvePeriod($request);

        $data = $service->generate(
            $startDate,
            $endDate,
            SalesHistoryReportController::buildFilters($request, $statuses),
            $request->input('group_by', 'none')
        );

        return $this->handleExport('sales-history', $data, $request->input('format'));
    }

    /**
     * Handle export based on format
     *
     * @param string $reportType
     * @param array $data
     * @param string $format
     * @return Response
     */
    private function handleExport(string $reportType, array $data, string $format): Response
    {
        try {
            $filePath = match ($format) {
                'csv' => $this->exportService->exportToCSV($reportType, $data),
                'pdf' => $this->exportService->exportToPDF($reportType, $data),
                'excel' => $this->exportService->exportToExcel($reportType, $data),
                default => throw new \InvalidArgumentException('Invalid export format'),
            };

            $contentType = match ($format) {
                'csv' => 'text/csv',
                'pdf' => 'application/pdf',
                'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            };

            $filename = basename($filePath);

            return response()->download($filePath, $filename, [
                'Content-Type' => $contentType,
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Export failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
