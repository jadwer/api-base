<?php

namespace Modules\Finance\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Services\LatePenaltyService;

class ARInvoiceController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    protected LatePenaltyService $latePenaltyService;

    public function __construct(LatePenaltyService $latePenaltyService)
    {
        $this->latePenaltyService = $latePenaltyService;
    }

    /**
     * FI-M001: Get late penalty calculation for an invoice.
     *
     * @param ARInvoice $arInvoice
     * @return JsonResponse
     */
    public function latePenalty(ARInvoice $arInvoice): JsonResponse
    {
        $penalty = $this->latePenaltyService->calculatePenalty($arInvoice);

        return response()->json([
            'data' => $penalty,
        ]);
    }

    /**
     * FI-M001: Apply late penalty to an invoice.
     *
     * @param Request $request
     * @param ARInvoice $arInvoice
     * @return JsonResponse
     */
    public function applyPenalty(Request $request, ARInvoice $arInvoice): JsonResponse
    {
        $penaltyAmount = $request->input('penalty_amount');
        $notes = $request->input('notes');

        $result = $this->latePenaltyService->applyPenalty($arInvoice, $penaltyAmount, $notes);

        return response()->json([
            'data' => $result,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * FI-M001: Get all overdue invoices with penalties.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overdueWithPenalties(Request $request): JsonResponse
    {
        $asOfDate = $request->has('as_of_date')
            ? \Carbon\Carbon::parse($request->input('as_of_date'))
            : null;

        $annualRate = $request->input('annual_rate');

        $overdueInvoices = $this->latePenaltyService->getOverdueInvoicesWithPenalties($asOfDate, $annualRate);

        return response()->json([
            'data' => [
                'as_of_date' => ($asOfDate ?? now())->format('Y-m-d'),
                'annual_rate' => $annualRate ?? 18.0,
                'total_invoices' => $overdueInvoices->count(),
                'total_outstanding' => round($overdueInvoices->sum('outstanding_amount'), 2),
                'total_penalties' => round($overdueInvoices->sum('penalty_amount'), 2),
                'invoices' => $overdueInvoices->values()->all(),
            ],
        ]);
    }

    /**
     * FI-M001: Get penalty summary grouped by customer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function penaltySummary(Request $request): JsonResponse
    {
        $asOfDate = $request->has('as_of_date')
            ? \Carbon\Carbon::parse($request->input('as_of_date'))
            : null;

        $summary = $this->latePenaltyService->getPenaltySummaryByCustomer($asOfDate);

        return response()->json([
            'data' => [
                'as_of_date' => ($asOfDate ?? now())->format('Y-m-d'),
                'customers' => $summary->all(),
            ],
        ]);
    }

    /**
     * FI-M001: Get aging report for overdue invoices.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function agingReport(Request $request): JsonResponse
    {
        $asOfDate = $request->has('as_of_date')
            ? \Carbon\Carbon::parse($request->input('as_of_date'))
            : null;

        $report = $this->latePenaltyService->getAgingReport($asOfDate);

        return response()->json([
            'data' => $report,
        ]);
    }
}
