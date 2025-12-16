<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\FinancialStatements\IncomeStatementService;

class IncomeStatementController extends Controller
{
    protected IncomeStatementService $incomeStatementService;

    public function __construct(IncomeStatementService $incomeStatementService)
    {
        $this->incomeStatementService = $incomeStatementService;
    }

    /**
     * Fetch income statement (Estado de Resultados)
     *
     * GET /api/v1/reports/income-statement
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get filter parameters - support both direct and filter.* formats
        $startDate = $request->input('startDate') ?? $request->input('filter.startDate');
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();

        $endDate = $request->input('endDate') ?? $request->input('filter.endDate');
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate income statement using service
        $incomeStatementData = $this->incomeStatementService->generate($startDate, $endDate, $currency);

        // Return simple JSON response
        return response()->json([
            'data' => $incomeStatementData,
            'meta' => [
                'reportType' => 'Estado de Resultados',
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
                'currency' => $currency,
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Fetch comparative income statement (comparison between periods)
     *
     * GET /api/v1/reports/income-statement/comparative
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function comparative(Request $request): JsonResponse
    {
        // Current period
        $currentStartDate = $request->input('currentStartDate') ?? $request->input('filter.currentStartDate');
        $currentStartDate = $currentStartDate ? Carbon::parse($currentStartDate) : Carbon::now()->startOfMonth();

        $currentEndDate = $request->input('currentEndDate') ?? $request->input('filter.currentEndDate');
        $currentEndDate = $currentEndDate ? Carbon::parse($currentEndDate) : Carbon::now();

        // Previous period
        $previousStartDate = $request->input('previousStartDate') ?? $request->input('filter.previousStartDate');
        $previousStartDate = $previousStartDate ? Carbon::parse($previousStartDate) : Carbon::now()->subMonth()->startOfMonth();

        $previousEndDate = $request->input('previousEndDate') ?? $request->input('filter.previousEndDate');
        $previousEndDate = $previousEndDate ? Carbon::parse($previousEndDate) : Carbon::now()->subMonth()->endOfMonth();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate both income statements
        $currentData = $this->incomeStatementService->generate($currentStartDate, $currentEndDate, $currency);
        $previousData = $this->incomeStatementService->generate($previousStartDate, $previousEndDate, $currency);

        // Calculate variance
        $variance = $this->calculateVariance($currentData, $previousData);

        return response()->json([
            'data' => [
                'current' => $currentData,
                'previous' => $previousData,
                'variance' => $variance,
            ],
            'meta' => [
                'reportType' => 'Estado de Resultados Comparativo',
                'currentPeriod' => [
                    'startDate' => $currentStartDate->toDateString(),
                    'endDate' => $currentEndDate->toDateString(),
                ],
                'previousPeriod' => [
                    'startDate' => $previousStartDate->toDateString(),
                    'endDate' => $previousEndDate->toDateString(),
                ],
                'currency' => $currency,
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Calculate variance between two income statements
     */
    private function calculateVariance(array $current, array $previous): array
    {
        $currentRevenue = $current['totalRevenue'] ?? 0;
        $previousRevenue = $previous['totalRevenue'] ?? 0;
        $currentExpenses = $current['totalExpenses'] ?? 0;
        $previousExpenses = $previous['totalExpenses'] ?? 0;
        $currentNetIncome = $current['netIncome'] ?? 0;
        $previousNetIncome = $previous['netIncome'] ?? 0;

        return [
            'revenueChange' => $currentRevenue - $previousRevenue,
            'revenueChangePercent' => $previousRevenue != 0
                ? round((($currentRevenue - $previousRevenue) / abs($previousRevenue)) * 100, 2)
                : 0,
            'expensesChange' => $currentExpenses - $previousExpenses,
            'expensesChangePercent' => $previousExpenses != 0
                ? round((($currentExpenses - $previousExpenses) / abs($previousExpenses)) * 100, 2)
                : 0,
            'netIncomeChange' => $currentNetIncome - $previousNetIncome,
            'netIncomeChangePercent' => $previousNetIncome != 0
                ? round((($currentNetIncome - $previousNetIncome) / abs($previousNetIncome)) * 100, 2)
                : 0,
        ];
    }
}
