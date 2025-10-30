<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\FinancialStatements\IncomeStatementService;
use Modules\Reports\JsonApi\V1\IncomeStatements\IncomeStatementRequest;
use Modules\Reports\JsonApi\V1\IncomeStatements\IncomeStatementResource;

class IncomeStatementController extends Controller
{
    protected IncomeStatementService $incomeStatementService;

    public function __construct(IncomeStatementService $incomeStatementService)
    {
        $this->incomeStatementService = $incomeStatementService;
    }

    /**
     * Fetch income statements (list view)
     *
     * GET /api/v1/reports/income-statements
     *
     * @param IncomeStatementRequest $request
     * @return DataResponse
     */
    public function index(IncomeStatementRequest $request): DataResponse
    {
        // Get filter parameters
        $startDate = $request->input('filter.startDate')
            ? Carbon::parse($request->input('filter.startDate'))
            : Carbon::now()->startOfMonth();
        $endDate = $request->input('filter.endDate')
            ? Carbon::parse($request->input('filter.endDate'))
            : Carbon::now();
        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate income statement using service
        $incomeStatementData = $this->incomeStatementService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $incomeStatement = (object) array_merge(['id' => '1'], $incomeStatementData);

        // Wrap in collection for JSON:API response
        $collection = collect([$incomeStatement]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(IncomeStatementResource::collection($collection));
    }

    /**
     * Fetch a single income statement
     *
     * GET /api/v1/reports/income-statements/{id}
     *
     * @param string $id
     * @param IncomeStatementRequest $request
     * @return DataResponse
     */
    public function show(string $id, IncomeStatementRequest $request): DataResponse
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

        // Generate income statement using service
        $incomeStatementData = $this->incomeStatementService->generate($startDate, $endDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $incomeStatement = (object) array_merge(['id' => $id], $incomeStatementData);

        // Return JSON:API response
        return DataResponse::make($incomeStatement)
            ->withResource(new IncomeStatementResource($incomeStatement));
    }
}
