<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\AgingReports\ARAgingReportService;

class ARAgingReportController extends Controller
{
    protected ARAgingReportService $arAgingReportService;

    public function __construct(ARAgingReportService $arAgingReportService)
    {
        $this->arAgingReportService = $arAgingReportService;
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
     * Fetch AR aging reports
     *
     * GET /api/v1/reports/ar-aging-reports
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.ar-aging-reports.index')) {
            return $error;
        }

        // Get filter parameters
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate AR aging report using service
        $data = $this->arAgingReportService->generate($asOfDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                [
                    'type' => 'ar-aging-reports',
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
     * Fetch a single AR aging report
     *
     * GET /api/v1/reports/ar-aging-reports/{id}
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.ar-aging-reports.show')) {
            return $error;
        }

        // Get filter parameters
        $asOfDate = $request->input('asOfDate') ?? $request->input('filter.asOfDate');
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate AR aging report using service
        $data = $this->arAgingReportService->generate($asOfDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                'type' => 'ar-aging-reports',
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
