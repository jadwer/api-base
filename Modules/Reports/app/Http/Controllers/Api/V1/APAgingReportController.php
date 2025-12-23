<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\AgingReports\APAgingReportService;

class APAgingReportController extends Controller
{
    protected APAgingReportService $apAgingReportService;

    public function __construct(APAgingReportService $apAgingReportService)
    {
        $this->apAgingReportService = $apAgingReportService;
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
     * Fetch AP aging reports
     *
     * GET /api/v1/reports/ap-aging-reports
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.ap-aging-reports.index')) {
            return $error;
        }

        // Get filter parameters
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate AP aging report using service
        $data = $this->apAgingReportService->generate($asOfDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                [
                    'type' => 'ap-aging-reports',
                    'id' => '1',
                    'attributes' => [
                        'asOfDate' => $data['asOfDate'],
                        'currency' => $data['currency'],
                        'agingBuckets' => $data['agingBuckets'],
                        'totals' => $data['totals'],
                        'generatedAt' => $data['generatedAt'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Fetch a single AP aging report
     *
     * GET /api/v1/reports/ap-aging-reports/{id}
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.ap-aging-reports.show')) {
            return $error;
        }

        // Get filter parameters
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate AP aging report using service
        $data = $this->apAgingReportService->generate($asOfDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                'type' => 'ap-aging-reports',
                'id' => $id,
                'attributes' => [
                    'asOfDate' => $data['asOfDate'],
                    'currency' => $data['currency'],
                    'agingBuckets' => $data['agingBuckets'],
                    'totals' => $data['totals'],
                    'generatedAt' => $data['generatedAt'],
                ],
            ],
        ]);
    }
}
