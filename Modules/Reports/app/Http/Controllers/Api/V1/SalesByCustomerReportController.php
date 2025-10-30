<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\SalesReports\SalesByCustomerReportService;
use Modules\Reports\JsonApi\V1\SalesByCustomerReports\SalesByCustomerReportRequest;
use Modules\Reports\JsonApi\V1\SalesByCustomerReports\SalesByCustomerReportResource;

class SalesByCustomerReportController extends Controller
{
    protected SalesByCustomerReportService $salesByCustomerReportService;

    public function __construct(SalesByCustomerReportService $salesByCustomerReportService)
    {
        $this->salesByCustomerReportService = $salesByCustomerReportService;
    }

    /**
     * Fetch sales by customer reports (list view)
     *
     * GET /api/v1/reports/sales-by-customer-reports
     *
     * @param SalesByCustomerReportRequest $request
     * @return DataResponse
     */
    public function index(SalesByCustomerReportRequest $request): DataResponse
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

        // Generate sales by customer report using service
        $salesByCustomerReportData = $this->salesByCustomerReportService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $salesByCustomerReport = (object) array_merge(['id' => '1'], $salesByCustomerReportData);

        // Wrap in collection for JSON:API response
        $collection = collect([$salesByCustomerReport]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(SalesByCustomerReportResource::collection($collection));
    }

    /**
     * Fetch a single balance sheet
     *
     * GET /api/v1/reports/sales-by-customer-reports/{id}
     *
     * @param string $id
     * @param SalesByCustomerReportRequest $request
     * @return DataResponse
     */
    public function show(string $id, SalesByCustomerReportRequest $request): DataResponse
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

        // Generate sales by customer report using service
        $salesByCustomerReportData = $this->salesByCustomerReportService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $salesByCustomerReport = (object) array_merge(['id' => $id], $salesByCustomerReportData);

        // Return JSON:API response
        return DataResponse::make($salesByCustomerReport)
            ->withResource(new SalesByCustomerReportResource($salesByCustomerReport));
    }
}
