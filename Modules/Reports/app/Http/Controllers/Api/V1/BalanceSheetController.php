<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\FinancialStatements\BalanceSheetService;
use Modules\Reports\JsonApi\V1\BalanceSheets\BalanceSheetRequest;
use Modules\Reports\JsonApi\V1\BalanceSheets\BalanceSheetResource;

class BalanceSheetController extends Controller
{
    protected BalanceSheetService $balanceSheetService;

    public function __construct(BalanceSheetService $balanceSheetService)
    {
        $this->balanceSheetService = $balanceSheetService;
    }

    /**
     * Fetch balance sheets (list view)
     *
     * GET /api/v1/reports/balance-sheets
     *
     * @param BalanceSheetRequest $request
     * @return DataResponse
     */
    public function index(BalanceSheetRequest $request): DataResponse
    {
        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();

        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate balance sheet using service
        $balanceSheetData = $this->balanceSheetService->generate($asOfDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $balanceSheet = (object) array_merge(['id' => '1'], $balanceSheetData);

        // Wrap in collection for JSON:API response
        $collection = collect([$balanceSheet]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(BalanceSheetResource::collection($collection));
    }

    /**
     * Fetch a single balance sheet
     *
     * GET /api/v1/reports/balance-sheets/{id}
     *
     * @param string $id
     * @param BalanceSheetRequest $request
     * @return DataResponse
     */
    public function show(string $id, BalanceSheetRequest $request): DataResponse
    {
        // For reports, ID doesn't matter much - we generate based on filters
        // But we support it for JSON:API compliance

        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();

        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate balance sheet using service
        $balanceSheetData = $this->balanceSheetService->generate($asOfDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $balanceSheet = (object) array_merge(['id' => $id], $balanceSheetData);

        // Return JSON:API response
        return DataResponse::make($balanceSheet)
            ->withResource(new BalanceSheetResource($balanceSheet));
    }
}
