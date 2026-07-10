<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Reports\Services\SalesReports\SalesHistoryReportService;
use Carbon\Carbon;

/**
 * Historico de Ventas (v1)
 *
 * Endpoint:
 * - GET /api/v1/reports/sales-history
 *
 * Reporte de trabajo diario estilo Bind ERP: una fila por orden de venta con
 * costo, utilidad, subtotal, descuento, IVA, total y estatus, con totales
 * globales, agrupacion opcional y paginacion server-side.
 */
class SalesHistoryReportController extends Controller
{
    private SalesHistoryReportService $reportService;

    public function __construct(SalesHistoryReportService $reportService)
    {
        $this->reportService = $reportService;
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
     * Generate Sales History Report
     *
     * GET /api/v1/reports/sales-history?start_date=2026-07-01&end_date=2026-07-09
     *     &contact_id=1&assigned_to=2&product_id=3&category_id=4&brand_id=5
     *     &status=confirmed,delivered&currency=MXN&iva=1&order_number=SO-
     *     &group_by=customer&page[number]=1&page[size]=25
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->authorize($request, 'reports.sales-history.index')) {
            return $error;
        }

        $request->validate(self::filterRules());

        $statuses = self::parseStatuses($request);

        [$startDate, $endDate] = self::resolvePeriod($request);

        $pageNumber = (int) $request->input('page.number', 1);
        $pageSize = (int) $request->input('page.size', 25);

        $report = $this->reportService->generate(
            $startDate,
            $endDate,
            self::buildFilters($request, $statuses),
            $request->input('group_by', 'none'),
            $pageNumber,
            $pageSize
        );

        return response()->json($report);
    }

    /**
     * Shared validation rules for report and export endpoints.
     */
    public static function filterRules(): array
    {
        return [
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'contact_id' => 'sometimes|integer|exists:contacts,id',
            'assigned_to' => 'sometimes|integer|exists:users,id',
            'product_id' => 'sometimes|integer|exists:products,id',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'brand_id' => 'sometimes|integer|exists:brands,id',
            'status' => 'sometimes|string',
            'currency' => 'sometimes|string|size:3',
            'iva' => 'sometimes|in:0,1,true,false',
            'order_number' => 'sometimes|string|max:100',
            'group_by' => ['sometimes', 'string', Rule::in(SalesHistoryReportService::GROUP_OPTIONS)],
            'page' => 'sometimes|array',
            'page.number' => 'sometimes|integer|min:1',
            'page.size' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Parse and validate the multi-value status CSV param.
     */
    public static function parseStatuses(Request $request): ?array
    {
        if (!$request->filled('status')) {
            return null;
        }

        $statuses = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $request->input('status'))
        )));

        validator(
            ['status' => $statuses],
            ['status.*' => [Rule::in(SalesHistoryReportService::STATUSES)]]
        )->validate();

        return $statuses;
    }

    /**
     * Resolve the report period (defaults to the current month).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function resolvePeriod(Request $request): array
    {
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        return [$startDate, $endDate];
    }

    /**
     * Build the filters array consumed by the service.
     */
    public static function buildFilters(Request $request, ?array $statuses): array
    {
        return [
            'contact_id' => $request->input('contact_id'),
            'assigned_to' => $request->input('assigned_to'),
            'product_id' => $request->input('product_id'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'statuses' => $statuses,
            'currency' => $request->input('currency'),
            'iva' => $request->has('iva')
                ? filter_var($request->input('iva'), FILTER_VALIDATE_BOOLEAN)
                : null,
            'order_number' => $request->input('order_number'),
        ];
    }
}
