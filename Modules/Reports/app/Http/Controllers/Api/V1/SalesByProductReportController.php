<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\SalesReports\SalesByProductReportService;
use Modules\Reports\JsonApi\V1\SalesByProductReports\SalesByProductReportRequest;

class SalesByProductReportController extends Controller
{
    protected SalesByProductReportService $salesByProductReportService;

    public function __construct(SalesByProductReportService $salesByProductReportService)
    {
        $this->salesByProductReportService = $salesByProductReportService;
    }

    /**
     * Fetch sales by product reports (list view)
     *
     * GET /api/v1/reports/sales-by-product-reports
     *
     * @param SalesByProductReportRequest $request
     * @return DataResponse
     */
    public function index(SalesByProductReportRequest $request): DataResponse
    {
        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();

        $startDate = $request->input('filter.startDate')
            ? Carbon::parse($request->input('filter.startDate'))
            : Carbon::now()->startOfMonth();
        $endDate = $request->input('filter.endDate')
            ? Carbon::parse($request->input('filter.endDate'))
            : Carbon::now();
        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate sales by product report using service
        $salesByProductReportData = $this->salesByProductReportService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $salesByProductReport = (object) array_merge(['id' => '1'], $salesByProductReportData);

        // Wrap in collection for JSON:API response
        $collection = collect([$salesByProductReport]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(SalesByProductReportResource::collection($collection));
    }

    /**
     * Fetch a single balance sheet
     *
     * GET /api/v1/reports/sales-by-product-reports/{id}
     *
     * @param string $id
     * @param SalesByProductReportRequest $request
     * @return DataResponse
     */
    public function show(string $id, SalesByProductReportRequest $request): DataResponse
    {
        // For reports, ID doesn't matter much - we generate based on filters
        // But we support it for JSON:API compliance

        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();

        $startDate = $request->input('filter.startDate')
            ? Carbon::parse($request->input('filter.startDate'))
            : Carbon::now()->startOfMonth();
        $endDate = $request->input('filter.endDate')
            ? Carbon::parse($request->input('filter.endDate'))
            : Carbon::now();
        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate sales by product report using service
        $salesByProductReportData = $this->salesByProductReportService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $salesByProductReport = (object) array_merge(['id' => $id], $salesByProductReportData);

        // Return JSON:API response
        return DataResponse::make($salesByProductReport)
            ->withResource(new SalesByProductReportResource($salesByProductReport));
    }
}
