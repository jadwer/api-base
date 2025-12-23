<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\SalesReports\SalesByProductReportService;

class SalesByProductReportController extends Controller
{
    protected SalesByProductReportService $salesByProductReportService;

    public function __construct(SalesByProductReportService $salesByProductReportService)
    {
        $this->salesByProductReportService = $salesByProductReportService;
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
     * Fetch sales by product reports
     *
     * GET /api/v1/reports/sales-by-product-reports
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.sales-by-product-reports.index')) {
            return $error;
        }

        // Get filter parameters
        $startDate = $request->input('startDate') ?? $request->input('filter.startDate');
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();

        $endDate = $request->input('endDate') ?? $request->input('filter.endDate');
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate report using service
        $data = $this->salesByProductReportService->generate($startDate, $endDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                [
                    'type' => 'sales-by-product-reports',
                    'id' => '1',
                    'attributes' => [
                        'startDate' => $data['startDate'],
                        'endDate' => $data['endDate'],
                        'currency' => $data['currency'],
                        'salesByProduct' => $data['salesByProduct'],
                        'summary' => $data['summary'],
                        'generatedAt' => $data['generatedAt'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Fetch a single sales by product report
     *
     * GET /api/v1/reports/sales-by-product-reports/{id}
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        // Check authorization
        if ($error = $this->authorize($request, 'reports.sales-by-product-reports.show')) {
            return $error;
        }

        // Get filter parameters
        $startDate = $request->input('startDate') ?? $request->input('filter.startDate');
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();

        $endDate = $request->input('endDate') ?? $request->input('filter.endDate');
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $currency = $request->input('currency') ?? $request->input('filter.currency') ?? 'MXN';

        // Generate report using service
        $data = $this->salesByProductReportService->generate($startDate, $endDate, $currency);

        // Return JSON:API formatted response
        return response()->json([
            'jsonapi' => ['version' => '1.0'],
            'data' => [
                'type' => 'sales-by-product-reports',
                'id' => $id,
                'attributes' => [
                    'startDate' => $data['startDate'],
                    'endDate' => $data['endDate'],
                    'currency' => $data['currency'],
                    'salesByProduct' => $data['salesByProduct'],
                    'summary' => $data['summary'],
                    'generatedAt' => $data['generatedAt'],
                ],
            ],
        ]);
    }
}
