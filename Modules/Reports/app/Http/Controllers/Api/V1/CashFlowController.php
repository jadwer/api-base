<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\FinancialStatements\CashFlowService;
use Modules\Reports\JsonApi\V1\CashFlows\CashFlowRequest;
use Modules\Reports\JsonApi\V1\CashFlows\CashFlowResource;

class CashFlowController extends Controller
{
    protected CashFlowService $cashFlowService;

    public function __construct(CashFlowService $cashFlowService)
    {
        $this->cashFlowService = $cashFlowService;
    }

    /**
     * Fetch cash flow statements (list view)
     *
     * GET /api/v1/reports/cash-flows
     *
     * @param CashFlowRequest $request
     * @return DataResponse
     */
    public function index(CashFlowRequest $request): DataResponse
    {
        // Get filter parameters
        $startDate = $request->input('filter.startDate')
            ? Carbon::parse($request->input('filter.startDate'))
            : Carbon::now()->startOfMonth();
        $endDate = $request->input('filter.endDate')
            ? Carbon::parse($request->input('filter.endDate'))
            : Carbon::now();
        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate cash flow statement using service
        $cashFlowData = $this->cashFlowService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $cashFlow = (object) array_merge(['id' => '1'], $cashFlowData);

        // Wrap in collection for JSON:API response
        $collection = collect([$cashFlow]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(CashFlowResource::collection($collection));
    }

    /**
     * Fetch a single cash flow statement
     *
     * GET /api/v1/reports/cash-flows/{id}
     *
     * @param string $id
     * @param CashFlowRequest $request
     * @return DataResponse
     */
    public function show(string $id, CashFlowRequest $request): DataResponse
    {
        // For reports, ID doesn't matter much - we generate based on filters
        // But we support it for JSON:API compliance

        // Get filter parameters
        $startDate = $request->input('filter.startDate')
            ? Carbon::parse($request->input('filter.startDate'))
            : Carbon::now()->startOfMonth();
        $endDate = $request->input('filter.endDate')
            ? Carbon::parse($request->input('filter.endDate'))
            : Carbon::now();
        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate cash flow statement using service
        $cashFlowData = $this->cashFlowService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $cashFlow = (object) array_merge(['id' => $id], $cashFlowData);

        // Return JSON:API response
        return DataResponse::make($cashFlow)
            ->withResource(new CashFlowResource($cashFlow));
    }
}
