<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Reports\Services\ManagementReports\AgingReportService;
use Carbon\Carbon;

class AgingReportController extends Controller
{
    private AgingReportService $agingReportService;

    public function __construct(AgingReportService $agingReportService)
    {
        $this->agingReportService = $agingReportService;
        $this->middleware('auth:sanctum');
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
                'errors' => [['status' => '401', 'title' => 'No autenticado.']],
            ], 401);
        }

        // God and admin roles have full access
        if ($user->hasAnyRole(['god', 'admin'])) {
            return null;
        }

        if (!$user->hasPermissionTo($permission)) {
            return response()->json([
                'jsonapi' => ['version' => '1.0'],
                'errors' => [['status' => '403', 'title' => 'No tiene permisos para esta acción.']],
            ], 403);
        }

        return null;
    }

    /**
     * Generate Accounts Receivable Aging Report
     *
     * GET /api/v1/reports/aging-ar?as_of_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ar(Request $request): JsonResponse
    {
        // Solo pedia sesion: un cliente del portal leia la cartera completa (22-11).
        if ($error = $this->authorize($request, 'reports.ar-aging-reports.index')) {
            return $error;
        }

        $request->validate([
            'as_of_date' => 'sometimes|date',
        ]);

        $asOfDate = $request->has('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $agingReport = $this->agingReportService->generateAR($asOfDate);

        return response()->json([
            'data' => $agingReport,
        ]);
    }

    /**
     * Generate Accounts Payable Aging Report
     *
     * GET /api/v1/reports/aging-ap?as_of_date=2025-10-28
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ap(Request $request): JsonResponse
    {
        // Solo pedia sesion: un cliente del portal leia la cartera completa (22-11).
        if ($error = $this->authorize($request, 'reports.ap-aging-reports.index')) {
            return $error;
        }

        $request->validate([
            'as_of_date' => 'sometimes|date',
        ]);

        $asOfDate = $request->has('as_of_date')
            ? Carbon::parse($request->input('as_of_date'))
            : Carbon::now();

        $agingReport = $this->agingReportService->generateAP($asOfDate);

        return response()->json([
            'data' => $agingReport,
        ]);
    }
}
