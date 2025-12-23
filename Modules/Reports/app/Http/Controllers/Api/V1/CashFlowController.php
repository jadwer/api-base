<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\FinancialStatements\CashFlowService;

class CashFlowController extends Controller
{
    protected CashFlowService $cashFlowService;

    public function __construct(CashFlowService $cashFlowService)
    {
        $this->cashFlowService = $cashFlowService;
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
     * Fetch cash flow statement (Estado de Flujo de Efectivo)
     *
     * GET /api/v1/reports/cash-flows
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.cash-flows.index')) {
            return $error;
        }

        // Get filter parameters - support both direct and filter.* formats
        $startDate = $request->input('startDate') ?? $request->input('filter.startDate');
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();

        $endDate = $request->input('endDate') ?? $request->input('filter.endDate');
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate cash flow statement using service
        $data = $this->cashFlowService->generate($startDate, $endDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                [
                    'type' => 'cash-flows',
                    'id' => '1',
                    'attributes' => [
                        'startDate' => $data['startDate'],
                        'endDate' => $data['endDate'],
                        'currency' => $data['currency'],
                        'beginningCash' => $data['beginningCash'],
                        'operatingActivities' => $data['operatingActivities'],
                        'investingActivities' => $data['investingActivities'],
                        'financingActivities' => $data['financingActivities'],
                        'netCashFlow' => $data['netCashFlow'],
                        'endingCash' => $data['endingCash'],
                        'generatedAt' => $data['generatedAt'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Fetch a single cash flow statement
     *
     * GET /api/v1/reports/cash-flows/{id}
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.cash-flows.show')) {
            return $error;
        }

        // Get filter parameters
        $startDate = $request->input('startDate') ?? $request->input('filter.startDate');
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();

        $endDate = $request->input('endDate') ?? $request->input('filter.endDate');
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate cash flow statement using service
        $data = $this->cashFlowService->generate($startDate, $endDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                'type' => 'cash-flows',
                'id' => $id,
                'attributes' => [
                    'startDate' => $data['startDate'],
                    'endDate' => $data['endDate'],
                    'currency' => $data['currency'],
                    'beginningCash' => $data['beginningCash'],
                    'operatingActivities' => $data['operatingActivities'],
                    'investingActivities' => $data['investingActivities'],
                    'financingActivities' => $data['financingActivities'],
                    'netCashFlow' => $data['netCashFlow'],
                    'endingCash' => $data['endingCash'],
                    'generatedAt' => $data['generatedAt'],
                ],
            ],
        ]);
    }

    /**
     * Fetch comparative cash flow statement (comparison between periods)
     *
     * GET /api/v1/reports/cash-flow/comparative
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

        // Generate both cash flow statements
        $currentData = $this->cashFlowService->generate($currentStartDate, $currentEndDate, $currency);
        $previousData = $this->cashFlowService->generate($previousStartDate, $previousEndDate, $currency);

        // Calculate variance
        $variance = $this->calculateVariance($currentData, $previousData);

        return response()->json([
            'data' => [
                'current' => $currentData,
                'previous' => $previousData,
                'variance' => $variance,
            ],
            'meta' => [
                'reportType' => 'Flujo de Efectivo Comparativo',
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
     * Calculate variance between two cash flow statements
     */
    private function calculateVariance(array $current, array $previous): array
    {
        $currentOperating = $current['operatingCashFlow'] ?? 0;
        $previousOperating = $previous['operatingCashFlow'] ?? 0;
        $currentInvesting = $current['investingCashFlow'] ?? 0;
        $previousInvesting = $previous['investingCashFlow'] ?? 0;
        $currentFinancing = $current['financingCashFlow'] ?? 0;
        $previousFinancing = $previous['financingCashFlow'] ?? 0;
        $currentNetChange = $current['netCashChange'] ?? 0;
        $previousNetChange = $previous['netCashChange'] ?? 0;

        return [
            'operatingChange' => $currentOperating - $previousOperating,
            'operatingChangePercent' => $previousOperating != 0
                ? round((($currentOperating - $previousOperating) / abs($previousOperating)) * 100, 2)
                : 0,
            'investingChange' => $currentInvesting - $previousInvesting,
            'investingChangePercent' => $previousInvesting != 0
                ? round((($currentInvesting - $previousInvesting) / abs($previousInvesting)) * 100, 2)
                : 0,
            'financingChange' => $currentFinancing - $previousFinancing,
            'financingChangePercent' => $previousFinancing != 0
                ? round((($currentFinancing - $previousFinancing) / abs($previousFinancing)) * 100, 2)
                : 0,
            'netCashChangeVariance' => $currentNetChange - $previousNetChange,
        ];
    }
}
