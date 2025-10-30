<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\Export\ExportService;
use Modules\Reports\Services\FinancialStatements\BalanceSheetService;
use Modules\Reports\Services\FinancialStatements\IncomeStatementService;
use Modules\Reports\Services\FinancialStatements\CashFlowService;
use Modules\Reports\Services\FinancialStatements\TrialBalanceService;
use Modules\Reports\Services\ManagementReports\AgingReportService;
use Modules\Reports\Services\ManagementReports\SalesReportService;
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
