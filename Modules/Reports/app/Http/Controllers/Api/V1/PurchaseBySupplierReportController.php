<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\PurchaseReports\PurchaseBySupplierReportService;
use Modules\Reports\JsonApi\V1\PurchaseBySupplierReports\PurchaseBySupplierReportRequest;
use Modules\Reports\JsonApi\V1\PurchaseBySupplierReports\PurchaseBySupplierReportResource;

class PurchaseBySupplierReportController extends Controller
{
    protected PurchaseBySupplierReportService $purchaseBySupplierReportService;

    public function __construct(PurchaseBySupplierReportService $purchaseBySupplierReportService)
    {
        $this->purchaseBySupplierReportService = $purchaseBySupplierReportService;
    }

    /**
     * Fetch balance sheets (list view)
     *
     * GET /api/v1/reports/purchase-by-supplier-reports
     *
     * @param PurchaseBySupplierReportRequest $request
     * @return DataResponse
     */
    public function index(PurchaseBySupplierReportRequest $request): DataResponse
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

        // Generate purchase by supplier report using service
        $purchaseBySupplierReportData = $this->purchaseBySupplierReportService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $purchaseBySupplierReport = (object) array_merge(['id' => '1'], $purchaseBySupplierReportData);

        // Wrap in collection for JSON:API response
        $collection = collect([$purchaseBySupplierReport]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(PurchaseBySupplierReportResource::collection($collection));
    }

    /**
     * Fetch a single balance sheet
     *
     * GET /api/v1/reports/purchase-by-supplier-reports/{id}
     *
     * @param string $id
     * @param PurchaseBySupplierReportRequest $request
     * @return DataResponse
     */
    public function show(string $id, PurchaseBySupplierReportRequest $request): DataResponse
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

        // Generate purchase by supplier report using service
        $purchaseBySupplierReportData = $this->purchaseBySupplierReportService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $purchaseBySupplierReport = (object) array_merge(['id' => $id], $purchaseBySupplierReportData);

        // Return JSON:API response
        return DataResponse::make($purchaseBySupplierReport)
            ->withResource(new PurchaseBySupplierReportResource($purchaseBySupplierReport));
    }
}
