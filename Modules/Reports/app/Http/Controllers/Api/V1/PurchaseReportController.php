<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\ManagementReports\PurchaseReportService;
use Carbon\Carbon;

class PurchaseReportController extends Controller
{
    private PurchaseReportService $purchaseReportService;

    public function __construct(PurchaseReportService $purchaseReportService)
    {
        $this->purchaseReportService = $purchaseReportService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Authorize the request against a specific permission.
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
     * Generate Purchase by Supplier Report
     *
     * GET /api/v1/reports/purchase-by-supplier?start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bySupplier(Request $request): JsonResponse
    {
        if ($error = $this->authorize($request, 'reports.purchase-by-supplier-reports.index')) {
            return $error;
        }

        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $report = $this->purchaseReportService->generateBySupplier($startDate, $endDate);

        return response()->json([
            'data' => $report,
        ]);
    }

    /**
     * Generate Purchase by Product Report
     *
     * GET /api/v1/reports/purchase-by-product?start_date=2025-01-01&end_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byProduct(Request $request): JsonResponse
    {
        if ($error = $this->authorize($request, 'reports.purchase-by-product-reports.index')) {
            return $error;
        }

        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $report = $this->purchaseReportService->generateByProduct($startDate, $endDate);

        return response()->json([
            'data' => $report,
        ]);
    }
}
