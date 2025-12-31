<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\BackorderService;

/**
 * SA-M002: Controller for Backorder resource.
 */
class BackorderController extends Controller
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

    protected BackorderService $backorderService;

    public function __construct(BackorderService $backorderService)
    {
        $this->backorderService = $backorderService;
    }

    /**
     * Manually fulfill a backorder.
     * POST /api/v1/backorders/{backorder}/fulfill
     */
    public function fulfill(Request $request, Backorder $backorder): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'create_shipment' => 'boolean',
        ]);

        try {
            $backorder = $this->backorderService->fulfillBackorder(
                $backorder,
                $request->quantity,
                $request->boolean('create_shipment', false)
            );

            return response()->json([
                'data' => [
                    'type' => 'backorders',
                    'id' => (string) $backorder->id,
                    'attributes' => [
                        'backorderNumber' => $backorder->backorder_number,
                        'status' => $backorder->status,
                        'fulfilledQuantity' => $backorder->fulfilled_quantity,
                        'remainingQuantity' => $backorder->remaining_quantity,
                    ],
                ],
                'meta' => [
                    'message' => 'Backorder cumplido exitosamente.',
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '422',
                        'title' => 'Validation Error',
                        'detail' => $e->getMessage(),
                    ],
                ],
            ], 422);
        }
    }

    /**
     * Cancel a backorder.
     * POST /api/v1/backorders/{backorder}/cancel
     */
    public function cancel(Request $request, Backorder $backorder): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $backorder = $this->backorderService->cancelBackorder($backorder, $request->reason);

            return response()->json([
                'data' => [
                    'type' => 'backorders',
                    'id' => (string) $backorder->id,
                    'attributes' => [
                        'backorderNumber' => $backorder->backorder_number,
                        'status' => $backorder->status,
                    ],
                ],
                'meta' => [
                    'message' => 'Backorder cancelado.',
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '422',
                        'title' => 'Validation Error',
                        'detail' => $e->getMessage(),
                    ],
                ],
            ], 422);
        }
    }

    /**
     * Get backorder summary for an order.
     * GET /api/v1/sales-orders/{order}/backorder-summary
     */
    public function orderSummary(SalesOrder $order): JsonResponse
    {
        $summary = $this->backorderService->getOrderBackorderSummary($order);

        return response()->json([
            'data' => [
                'type' => 'backorder-summaries',
                'id' => (string) $order->id,
                'attributes' => $summary,
            ],
        ]);
    }

    /**
     * Fulfill pending backorders for a product when stock arrives.
     * POST /api/v1/backorders/fulfill-for-product
     */
    public function fulfillForProduct(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'max_quantity' => 'nullable|numeric|min:0.01',
        ]);

        $fulfilled = $this->backorderService->fulfillBackordersForProduct(
            $request->product_id,
            $request->warehouse_id,
            $request->max_quantity
        );

        $totalFulfilled = collect($fulfilled)->sum('fulfilled_quantity');

        return response()->json([
            'data' => [
                'type' => 'backorder-fulfillments',
                'id' => (string) $request->product_id,
                'attributes' => [
                    'productId' => $request->product_id,
                    'totalFulfilled' => $totalFulfilled,
                    'backordersProcessed' => count($fulfilled),
                    'details' => collect($fulfilled)->map(fn($f) => [
                        'backorderId' => $f['backorder']->id,
                        'backorderNumber' => $f['backorder']->backorder_number,
                        'fulfilledQuantity' => $f['fulfilled_quantity'],
                        'remaining' => $f['remaining'],
                        'status' => $f['backorder']->status,
                    ]),
                ],
            ],
            'meta' => [
                'message' => $totalFulfilled > 0
                    ? "Se cumplieron {$totalFulfilled} unidades en " . count($fulfilled) . " backorders."
                    : 'No hay backorders pendientes para este producto.',
            ],
        ]);
    }

    /**
     * Get pending backorders for a product.
     * GET /api/v1/backorders/pending-for-product/{productId}
     */
    public function pendingForProduct(int $productId): JsonResponse
    {
        $pending = $this->backorderService->getPendingBackordersForProduct($productId);

        return response()->json([
            'data' => [
                'type' => 'pending-backorders',
                'id' => (string) $productId,
                'attributes' => [
                    'productId' => $productId,
                    'totalPending' => count($pending),
                    'totalQuantity' => collect($pending)->sum('remaining_quantity'),
                    'backorders' => $pending,
                ],
            ],
        ]);
    }
}
