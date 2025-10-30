<?php

namespace Modules\Reports\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Reports\Services\FinancialStatements\TrialBalanceService;
use Modules\Reports\JsonApi\V1\TrialBalances\TrialBalanceRequest;
use Modules\Reports\JsonApi\V1\TrialBalances\TrialBalanceResource;

class TrialBalanceController extends Controller
{
    protected TrialBalanceService $trialBalanceService;

    public function __construct(TrialBalanceService $trialBalanceService)
    {
        $this->trialBalanceService = $trialBalanceService;
    }

    /**
     * Fetch trial balances (list view)
     *
     * GET /api/v1/reports/trial-balances
     *
     * @param TrialBalanceRequest $request
     * @return DataResponse
     */
    public function index(TrialBalanceRequest $request): DataResponse
    {
        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();
        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate trial balance using service
        $trialBalanceData = $this->trialBalanceService->generate($asOfDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $trialBalance = (object) array_merge(['id' => '1'], $trialBalanceData);

        // Wrap in collection for JSON:API response
        $collection = collect([$trialBalance]);

        // Return JSON:API response
        return DataResponse::make($collection)
            ->withResources(TrialBalanceResource::collection($collection));
    }

    /**
     * Fetch a single trial balance
     *
     * GET /api/v1/reports/trial-balances/{id}
     *
     * @param string $id
     * @param TrialBalanceRequest $request
     * @return DataResponse
     */
    public function show(string $id, TrialBalanceRequest $request): DataResponse
    {
        // For reports, ID doesn't matter much - we generate based on filters
        // But we support it for JSON:API compliance

        // Get filter parameters
        $asOfDate = $request->input('filter.asOfDate')
            ? Carbon::parse($request->input('filter.asOfDate'))
            : Carbon::now();
        $currency = $request->input('filter.currency') ?? 'MXN';

        // Generate trial balance using service
        $trialBalanceData = $this->trialBalanceService->generate($asOfDate, $currency);

        // Convert to stdClass with ID for JSON:API compliance
        $trialBalance = (object) array_merge(['id' => $id], $trialBalanceData);

        // Return JSON:API response
        return DataResponse::make($trialBalance)
            ->withResource(new TrialBalanceResource($trialBalance));
    }
}
