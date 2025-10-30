<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\AgingReports\ARAgingReportService;
use Modules\Reports\JsonApi\V1\ARAgingReports\ARAgingReportRequest;
use Modules\Reports\JsonApi\V1\ARAgingReports\ARAgingReportResource;

class ARAgingReportController extends Controller
{
    protected ARAgingReportService $arAgingReportService;

    public function __construct(ARAgingReportService $arAgingReportService)
    {
        $this->arAgingReportService = $arAgingReportService;
    }

    /**
     * Fetch AR aging reports (list view)
     *
     * GET /api/v1/reports/ar-aging-reports
     *
     * @param ARAgingReportRequest $request
     * @return DataResponse
     */
    public function index(ARAgingReportRequest $request): DataResponse
    {
        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();

        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate AR aging report using service
        $arAgingReportData = $this->arAgingReportService->generate($asOfDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $arAgingReport = (object) array_merge(['id' => '1'], $arAgingReportData);

        // Wrap in collection for JSON:API response
        $collection = collect([$arAgingReport]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(ARAgingReportResource::collection($collection));
    }

    /**
     * Fetch a single balance sheet
     *
     * GET /api/v1/reports/ar-aging-reports/{id}
     *
     * @param string $id
     * @param ARAgingReportRequest $request
     * @return DataResponse
     */
    public function show(string $id, ARAgingReportRequest $request): DataResponse
    {
        // For reports, ID doesn't matter much - we generate based on filters
        // But we support it for JSON:API compliance

        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();

        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate AR aging report using service
        $arAgingReportData = $this->arAgingReportService->generate($asOfDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $arAgingReport = (object) array_merge(['id' => $id], $arAgingReportData);

        // Return JSON:API response
        return DataResponse::make($arAgingReport)
            ->withResource(new ARAgingReportResource($arAgingReport));
    }
}
