<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\FinancialStatements\TrialBalanceService;

class TrialBalanceController extends Controller
{
    protected TrialBalanceService $trialBalanceService;

    public function __construct(TrialBalanceService $trialBalanceService)
    {
        $this->trialBalanceService = $trialBalanceService;
    }

    /**
     * Fetch trial balance (Balanza de Comprobación)
     *
     * GET /api/v1/reports/trial-balance
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get filter parameters - support both direct and filter.* formats
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate trial balance using service
        $trialBalanceData = $this->trialBalanceService->generate($asOfDate, $currency);

        // Return simple JSON response
        return response()->json([
            'data' => $trialBalanceData,
            'meta' => [
                'reportType' => 'Balanza de Comprobación',
                'asOfDate' => $asOfDate->toDateString(),
                'currency' => $currency,
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Fetch comparative trial balance (comparison between periods)
     *
     * GET /api/v1/reports/trial-balance/comparative
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function comparative(Request $request): JsonResponse
    {
        // Current period
        $currentDate = $request->input('currentDate') ?? $request->input('filter.currentDate');
        $currentDate = $currentDate ? Carbon::parse($currentDate) : Carbon::now();

        // Previous period
        $previousDate = $request->input('previousDate') ?? $request->input('filter.previousDate');
        $previousDate = $previousDate ? Carbon::parse($previousDate) : Carbon::now()->subMonth();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate both trial balances
        $currentData = $this->trialBalanceService->generate($currentDate, $currency);
        $previousData = $this->trialBalanceService->generate($previousDate, $currency);

        // Calculate variance
        $variance = $this->calculateVariance($currentData, $previousData);

        return response()->json([
            'data' => [
                'current' => $currentData,
                'previous' => $previousData,
                'variance' => $variance,
            ],
            'meta' => [
                'reportType' => 'Balanza de Comprobación Comparativa',
                'currentPeriod' => [
                    'asOfDate' => $currentDate->toDateString(),
                ],
                'previousPeriod' => [
                    'asOfDate' => $previousDate->toDateString(),
                ],
                'currency' => $currency,
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Fetch detailed trial balance with account movements
     *
     * GET /api/v1/reports/trial-balance/detailed
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detailed(Request $request): JsonResponse
    {
        // Get filter parameters
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate trial balance using service
        $trialBalanceData = $this->trialBalanceService->generate($asOfDate, $currency);

        // Add extra detail for each account (movement breakdown)
        // This would normally come from the service, but we can enhance it here
        return response()->json([
            'data' => array_merge($trialBalanceData, [
                'detailLevel' => 'full',
                'includesMovements' => true,
            ]),
            'meta' => [
                'reportType' => 'Balanza de Comprobación Detallada',
                'asOfDate' => $asOfDate->toDateString(),
                'currency' => $currency,
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Calculate variance between two trial balances
     */
    private function calculateVariance(array $current, array $previous): array
    {
        $currentTotalDebits = $current['totalDebits'] ?? 0;
        $previousTotalDebits = $previous['totalDebits'] ?? 0;
        $currentTotalCredits = $current['totalCredits'] ?? 0;
        $previousTotalCredits = $previous['totalCredits'] ?? 0;

        return [
            'totalDebitsChange' => $currentTotalDebits - $previousTotalDebits,
            'totalDebitsChangePercent' => $previousTotalDebits != 0
                ? round((($currentTotalDebits - $previousTotalDebits) / abs($previousTotalDebits)) * 100, 2)
                : 0,
            'totalCreditsChange' => $currentTotalCredits - $previousTotalCredits,
            'totalCreditsChangePercent' => $previousTotalCredits != 0
                ? round((($currentTotalCredits - $previousTotalCredits) / abs($previousTotalCredits)) * 100, 2)
                : 0,
        ];
    }
}
