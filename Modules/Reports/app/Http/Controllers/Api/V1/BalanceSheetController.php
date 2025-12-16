<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\FinancialStatements\BalanceSheetService;

class BalanceSheetController extends Controller
{
    protected BalanceSheetService $balanceSheetService;

    public function __construct(BalanceSheetService $balanceSheetService)
    {
        $this->balanceSheetService = $balanceSheetService;
    }

    /**
     * Fetch balance sheet
     *
     * GET /api/v1/reports/balance-sheet
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        $balanceSheetData = $this->balanceSheetService->generate($asOfDate, $currency);

        return response()->json([
            'data' => $balanceSheetData,
            'meta' => [
                'reportType' => 'Balance General',
                'asOfDate' => $asOfDate->toDateString(),
                'currency' => $currency,
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Fetch comparative balance sheet
     *
     * GET /api/v1/reports/balance-sheet/comparative
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function comparative(Request $request): JsonResponse
    {
        $currentDate = $request->input('currentDate')
            ? Carbon::parse($request->input('currentDate'))
            : Carbon::now();

        $previousDate = $request->input('previousDate')
            ? Carbon::parse($request->input('previousDate'))
            : $currentDate->copy()->subYear();

        $currency = $request->input('currency') ?? 'MXN';

        $currentData = $this->balanceSheetService->generate($currentDate, $currency);
        $previousData = $this->balanceSheetService->generate($previousDate, $currency);

        return response()->json([
            'data' => [
                'current' => $currentData,
                'previous' => $previousData,
                'variance' => $this->calculateVariance($currentData, $previousData),
            ],
            'meta' => [
                'reportType' => 'Balance General Comparativo',
                'currentPeriod' => $currentDate->toDateString(),
                'previousPeriod' => $previousDate->toDateString(),
                'currency' => $currency,
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Calculate variance between two periods
     */
    private function calculateVariance(array $current, array $previous): array
    {
        $currentAssets = $current['totalAssets'] ?? 0;
        $previousAssets = $previous['totalAssets'] ?? 0;
        $assetsChange = $currentAssets - $previousAssets;
        $assetsChangePercent = $previousAssets != 0 ? ($assetsChange / $previousAssets) * 100 : 0;

        $currentLiabilities = $current['totalLiabilities'] ?? 0;
        $previousLiabilities = $previous['totalLiabilities'] ?? 0;
        $liabilitiesChange = $currentLiabilities - $previousLiabilities;
        $liabilitiesChangePercent = $previousLiabilities != 0 ? ($liabilitiesChange / $previousLiabilities) * 100 : 0;

        $currentEquity = $current['totalEquity'] ?? 0;
        $previousEquity = $previous['totalEquity'] ?? 0;
        $equityChange = $currentEquity - $previousEquity;
        $equityChangePercent = $previousEquity != 0 ? ($equityChange / $previousEquity) * 100 : 0;

        return [
            'totalAssetsChange' => round($assetsChange, 2),
            'totalAssetsChangePercent' => round($assetsChangePercent, 2),
            'totalLiabilitiesChange' => round($liabilitiesChange, 2),
            'totalLiabilitiesChangePercent' => round($liabilitiesChangePercent, 2),
            'totalEquityChange' => round($equityChange, 2),
            'totalEquityChangePercent' => round($equityChangePercent, 2),
        ];
    }
}
