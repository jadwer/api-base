<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\PurchaseReports\PurchaseByProductReportService;
use Modules\Reports\JsonApi\V1\PurchaseByProductReports\PurchaseByProductReportRequest;
use Modules\Reports\JsonApi\V1\PurchaseByProductReports\PurchaseByProductReportResource;

class PurchaseByProductReportController extends Controller
{
    protected PurchaseByProductReportService $purchaseByProductReportService;

    public function __construct(PurchaseByProductReportService $purchaseByProductReportService)
    {
        $this->purchaseByProductReportService = $purchaseByProductReportService;
    }

    /**
     * Fetch balance sheets (list view)
     *
     * GET /api/v1/reports/purchase-by-product-reports
     *
     * @param PurchaseByProductReportRequest $request
     * @return DataResponse
     */
    public function index(PurchaseByProductReportRequest $request): DataResponse
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

        // Generate purchase by product report using service
        $purchaseByProductReportData = $this->purchaseByProductReportService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $purchaseByProductReport = (object) array_merge(['id' => '1'], $purchaseByProductReportData);

        // Wrap in collection for JSON:API response
        $collection = collect([$purchaseByProductReport]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(PurchaseByProductReportResource::collection($collection));
    }

    /**
     * Fetch a single balance sheet
     *
     * GET /api/v1/reports/purchase-by-product-reports/{id}
     *
     * @param string $id
     * @param PurchaseByProductReportRequest $request
     * @return DataResponse
     */
    public function show(string $id, PurchaseByProductReportRequest $request): DataResponse
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

        // Generate purchase by product report using service
        $purchaseByProductReportData = $this->purchaseByProductReportService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $purchaseByProductReport = (object) array_merge(['id' => $id], $purchaseByProductReportData);

        // Return JSON:API response
        return DataResponse::make($purchaseByProductReport)
            ->withResource(new PurchaseByProductReportResource($purchaseByProductReport));
    }
}
