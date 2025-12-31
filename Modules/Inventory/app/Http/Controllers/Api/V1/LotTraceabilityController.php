<?php

namespace Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Services\LotTraceabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LotTraceabilityController
 *
 * IV-M003: API endpoints for lot traceability features.
 *
 * Provides:
 * - Batch genealogy (complete movement history)
 * - Backward tracing (where did it come from?)
 * - Forward tracing (where did it go?)
 * - FEFO batch selection
 * - Expiration monitoring
 * - Batch search
 * - Recall impact analysis
 */
class LotTraceabilityController extends Controller
{
    public function __construct(
        private LotTraceabilityService $traceabilityService
    ) {}

    /**
     * Get complete genealogy for a batch.
     *
     * GET /api/v1/lot-traceability/{batch}/genealogy
     */
    public function genealogy(ProductBatch $batch): JsonResponse
    {
        // Authorization handled by auth:sanctum middleware
        $genealogy = $this->traceabilityService->getBatchGenealogy($batch);

        return response()->json([
            'data' => $genealogy,
        ]);
    }

    /**
     * Trace batch backward to find origin.
     *
     * GET /api/v1/lot-traceability/{batch}/trace-backward
     */
    public function traceBackward(ProductBatch $batch): JsonResponse
    {
        $trace = $this->traceabilityService->traceBackward($batch);

        return response()->json([
            'data' => $trace,
        ]);
    }

    /**
     * Trace batch forward to find where it went.
     *
     * GET /api/v1/lot-traceability/{batch}/trace-forward
     */
    public function traceForward(ProductBatch $batch): JsonResponse
    {
        $trace = $this->traceabilityService->traceForward($batch);

        return response()->json([
            'data' => $trace,
        ]);
    }

    /**
     * Select batches using FEFO algorithm.
     *
     * POST /api/v1/lot-traceability/select-fefo
     *
     * Request body:
     * {
     *   "product_id": 1,
     *   "warehouse_id": 1,
     *   "quantity": 100,
     *   "location_id": null (optional)
     * }
     */
    public function selectFEFO(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.0001',
            'location_id' => 'nullable|integer|exists:warehouse_locations,id',
        ]);

        $selection = $this->traceabilityService->selectBatchesFEFO(
            $validated['product_id'],
            $validated['warehouse_id'],
            $validated['quantity'],
            $validated['location_id'] ?? null
        );

        return response()->json([
            'data' => $selection,
        ]);
    }

    /**
     * Get batches expiring soon.
     *
     * GET /api/v1/lot-traceability/expiring-soon
     *
     * Query params:
     * - product_id (optional)
     * - warehouse_id (optional)
     * - days (optional, default 30)
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $batches = $this->traceabilityService->getExpiringSoonBatches(
            $validated['product_id'] ?? null,
            $validated['warehouse_id'] ?? null,
            $validated['days'] ?? 30
        );

        return response()->json([
            'data' => $batches,
            'meta' => [
                'count' => $batches->count(),
                'threshold_days' => $validated['days'] ?? 30,
            ],
        ]);
    }

    /**
     * Get already expired batches.
     *
     * GET /api/v1/lot-traceability/expired
     *
     * Query params:
     * - warehouse_id (optional)
     */
    public function expired(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $batches = $this->traceabilityService->getExpiredBatches(
            $validated['warehouse_id'] ?? null
        );

        return response()->json([
            'data' => $batches,
            'meta' => [
                'count' => $batches->count(),
                'total_value_at_risk' => $batches->sum('total_value'),
            ],
        ]);
    }

    /**
     * Search batches by lot/batch number.
     *
     * GET /api/v1/lot-traceability/search
     *
     * Query params:
     * - q (required, search term)
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $batches = $this->traceabilityService->searchBatches($validated['q']);

        return response()->json([
            'data' => $batches,
            'meta' => [
                'count' => $batches->count(),
                'search_term' => $validated['q'],
            ],
        ]);
    }

    /**
     * Get recall impact analysis for a batch.
     *
     * GET /api/v1/lot-traceability/{batch}/recall-impact
     */
    public function recallImpact(ProductBatch $batch): JsonResponse
    {
        $impact = $this->traceabilityService->getRecallImpactAnalysis($batch);

        return response()->json([
            'data' => $impact,
        ]);
    }

    /**
     * Initiate recall for a batch.
     *
     * POST /api/v1/lot-traceability/{batch}/initiate-recall
     *
     * Request body:
     * {
     *   "reason": "Quality issue detected in supplier batch"
     * }
     */
    public function initiateRecall(Request $request, ProductBatch $batch): JsonResponse
    {

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        $success = $this->traceabilityService->initiateRecall(
            $batch,
            $validated['reason'],
            auth()->id()
        );

        if (!$success) {
            return response()->json([
                'error' => 'Failed to initiate recall',
            ], 500);
        }

        // Reload batch to get updated data
        $batch->refresh();

        return response()->json([
            'message' => 'Recall initiated successfully',
            'data' => [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'status' => $batch->status,
                'quality_status' => $batch->quality_status,
            ],
        ]);
    }
}
