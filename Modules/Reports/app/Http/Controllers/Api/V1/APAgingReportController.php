<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\AgingReports\APAgingReportService;
use Modules\Reports\JsonApi\V1\APAgingReports\APAgingReportRequest;
use Modules\Reports\JsonApi\V1\APAgingReports\APAgingReportResource;

class APAgingReportController extends Controller
{
    protected APAgingReportService $apAgingReportService;

    public function __construct(APAgingReportService $apAgingReportService)
    {
        $this->apAgingReportService = $apAgingReportService;
    }

    /**
     * Fetch AP aging reports (list view)
     *
     * GET /api/v1/reports/ap-aging-reports
     *
     * @param APAgingReportRequest $request
     * @return DataResponse
     */
    public function index(APAgingReportRequest $request): DataResponse
    {
        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();

        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate AP aging report using service
        $apAgingReportData = $this->apAgingReportService->generate($asOfDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $apAgingReport = (object) array_merge(['id' => '1'], $apAgingReportData);

        // Wrap in collection for JSON:API response
        $collection = collect([$apAgingReport]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(APAgingReportResource::collection($collection));
    }

    /**
     * Fetch a single balance sheet
     *
     * GET /api/v1/reports/ap-aging-reports/{id}
     *
     * @param string $id
     * @param APAgingReportRequest $request
     * @return DataResponse
     */
    public function show(string $id, APAgingReportRequest $request): DataResponse
    {
        // For reports, ID doesn't matter much - we generate based on filters
        // But we support it for JSON:API compliance

        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();

        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate AP aging report using service
        $apAgingReportData = $this->apAgingReportService->generate($asOfDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $apAgingReport = (object) array_merge(['id' => $id], $apAgingReportData);

        // Return JSON:API response
        return DataResponse::make($apAgingReport)
            ->withResource(new APAgingReportResource($apAgingReport));
    }
}
