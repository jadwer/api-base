<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\Analytics\KPIService;
use Modules\Reports\Services\Analytics\MetricsService;
use Modules\Reports\Services\Analytics\TrendAnalysisService;

class AnalyticsController extends Controller
{
    private KPIService $kpiService;
    private MetricsService $metricsService;
    private TrendAnalysisService $trendAnalysisService;

    public function __construct(
        KPIService $kpiService,
        MetricsService $metricsService,
        TrendAnalysisService $trendAnalysisService
    ) {
        $this->kpiService = $kpiService;
        $this->metricsService = $metricsService;
        $this->trendAnalysisService = $trendAnalysisService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get Key Performance Indicators
     *
     * GET /api/v1/analytics/kpis
     *
     * @return JsonResponse
     */
    public function kpis(): JsonResponse
    {
        $kpis = $this->kpiService->getKPIs();

        return response()->json([
            'data' => $kpis,
        ]);
    }

    /**
     * Get Real-time Business Metrics
     *
     * GET /api/v1/analytics/metrics
     *
     * @return JsonResponse
     */
    public function metrics(): JsonResponse
    {
        $metrics = $this->metricsService->getMetrics();

        return response()->json([
            'data' => $metrics,
        ]);
    }

    /**
     * Get Trend Analysis
     *
     * GET /api/v1/analytics/trends?metric=revenue&period=12months
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trends(Request $request): JsonResponse
    {
        $request->validate([
            'metric' => 'required|in:revenue,sales,purchases,expenses,profit',
            'period' => 'sometimes|in:6months,12months,24months',
        ]);

        $metric = $request->input('metric');
        $period = $request->input('period', '12months');

        $trends = $this->trendAnalysisService->generateTrend($metric, $period);

        return response()->json([
            'data' => $trends,
        ]);
    }

    /**
     * Get complete analytics dashboard data
     *
     * GET /api/v1/analytics/dashboard
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $kpis = $this->kpiService->getKPIs();
        $metrics = $this->metricsService->getMetrics();
        $revenueTrend = $this->trendAnalysisService->generateTrend('revenue', '12months');
        $profitTrend = $this->trendAnalysisService->generateTrend('profit', '12months');

        return response()->json([
            'data' => [
                'kpis' => $kpis,
                'metrics' => $metrics,
                'trends' => [
                    'revenue' => $revenueTrend,
                    'profit' => $profitTrend,
                ],
            ],
        ]);
    }
}
