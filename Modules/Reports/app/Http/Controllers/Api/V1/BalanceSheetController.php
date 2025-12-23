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
     * Check if user has permission for reports
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

        if (!$user->hasPermissionTo($permission)) {
            return response()->json([
                'jsonapi' => ['version' => '1.0'],
                'errors' => [['status' => '403', 'title' => 'Forbidden']],
            ], 403);
        }

        return null;
    }

    /**
     * Fetch balance sheet (Balance General)
     *
     * GET /api/v1/reports/balance-sheets
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.balance-sheets.index')) {
            return $error;
        }

        // Get filter parameters - support both direct and filter.* formats
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate balance sheet using service
        $balanceSheetData = $this->balanceSheetService->generate($asOfDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                [
                    'type' => 'balance-sheets',
                    'id' => '1',
                    'attributes' => [
                        'asOfDate' => $balanceSheetData['asOfDate'],
                        'currency' => $balanceSheetData['currency'],
                        'balanced' => $balanceSheetData['balanced'],
                        'assets' => $balanceSheetData['assets'],
                        'liabilities' => $balanceSheetData['liabilities'],
                        'equity' => $balanceSheetData['equity'],
                        'totalAssets' => $balanceSheetData['totalAssets'],
                        'totalLiabilities' => $balanceSheetData['totalLiabilities'],
                        'totalEquity' => $balanceSheetData['totalEquity'],
                        'generatedAt' => $balanceSheetData['generatedAt'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Fetch a single balance sheet
     *
     * GET /api/v1/reports/balance-sheets/{id}
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.balance-sheets.show')) {
            return $error;
        }

        // Get filter parameters
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate balance sheet using service
        $balanceSheetData = $this->balanceSheetService->generate($asOfDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                'type' => 'balance-sheets',
                'id' => $id,
                'attributes' => [
                    'asOfDate' => $balanceSheetData['asOfDate'],
                    'currency' => $balanceSheetData['currency'],
                    'balanced' => $balanceSheetData['balanced'],
                    'assets' => $balanceSheetData['assets'],
                    'liabilities' => $balanceSheetData['liabilities'],
                    'equity' => $balanceSheetData['equity'],
                    'totalAssets' => $balanceSheetData['totalAssets'],
                    'totalLiabilities' => $balanceSheetData['totalLiabilities'],
                    'totalEquity' => $balanceSheetData['totalEquity'],
                    'generatedAt' => $balanceSheetData['generatedAt'],
                ],
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
