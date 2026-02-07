<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Shipment;
use Modules\Sales\Services\ShipmentService;

/**
 * SA-M001: Controller for Shipment resource.
 */
class ShipmentController extends Controller
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

    protected ShipmentService $shipmentService;

    public function __construct(ShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * Create a shipment for an order with items.
     * POST /api/v1/shipments/create-from-order
     */
    public function createFromOrder(Request $request): JsonResponse
    {
        if (Gate::denies('shipments.store')) {
            abort(403, 'No tiene permisos para crear envios');
        }

        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'carrier' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order = SalesOrder::findOrFail($request->sales_order_id);

        // Convert items array to the format expected by service
        $items = collect($request->items)->mapWithKeys(function ($item) {
            return [$item['sales_order_item_id'] => $item['quantity']];
        })->all();

        $shipmentData = $request->only(['carrier', 'tracking_number', 'warehouse_id', 'notes', 'shipping_address']);

        try {
            $shipment = $this->shipmentService->createShipment($order, $items, $shipmentData);
            $shipment->load(['items.salesOrderItem.product', 'salesOrder']);

            return response()->json([
                'data' => [
                    'type' => 'shipments',
                    'id' => (string) $shipment->id,
                    'attributes' => [
                        'shipmentNumber' => $shipment->shipment_number,
                        'status' => $shipment->status,
                        'carrier' => $shipment->carrier,
                        'trackingNumber' => $shipment->tracking_number,
                        'totalItems' => $shipment->total_items,
                        'totalQuantity' => $shipment->total_quantity,
                        'createdAt' => $shipment->created_at->toIso8601String(),
                    ],
                ],
                'meta' => [
                    'message' => 'Envio creado exitosamente.',
                ],
            ], 201);
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
     * Mark shipment as shipped.
     * POST /api/v1/shipments/{shipment}/ship
     */
    public function ship(Request $request, Shipment $shipment): JsonResponse
    {
        if (Gate::denies('shipments.update')) {
            abort(403, 'No tiene permisos para marcar envios');
        }

        $request->validate([
            'tracking_number' => 'nullable|string|max:100',
            'carrier' => 'nullable|string|max:100',
        ]);

        try {
            $shipment = $this->shipmentService->markAsShipped(
                $shipment,
                $request->tracking_number,
                $request->carrier
            );

            return response()->json([
                'data' => [
                    'type' => 'shipments',
                    'id' => (string) $shipment->id,
                    'attributes' => [
                        'shipmentNumber' => $shipment->shipment_number,
                        'status' => $shipment->status,
                        'shipDate' => $shipment->ship_date?->toDateString(),
                        'trackingNumber' => $shipment->tracking_number,
                        'carrier' => $shipment->carrier,
                    ],
                ],
                'meta' => [
                    'message' => 'Envio marcado como enviado.',
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
     * Mark shipment as delivered.
     * POST /api/v1/shipments/{shipment}/deliver
     */
    public function deliver(Request $request, Shipment $shipment): JsonResponse
    {
        if (Gate::denies('shipments.update')) {
            abort(403, 'No tiene permisos para marcar envios como entregados');
        }

        $request->validate([
            'actual_delivery' => 'nullable|date',
        ]);

        try {
            $shipment = $this->shipmentService->markAsDelivered(
                $shipment,
                $request->actual_delivery
            );

            return response()->json([
                'data' => [
                    'type' => 'shipments',
                    'id' => (string) $shipment->id,
                    'attributes' => [
                        'shipmentNumber' => $shipment->shipment_number,
                        'status' => $shipment->status,
                        'actualDelivery' => $shipment->actual_delivery?->toDateString(),
                    ],
                ],
                'meta' => [
                    'message' => 'Envio marcado como entregado.',
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
     * Cancel a shipment.
     * POST /api/v1/shipments/{shipment}/cancel
     */
    public function cancel(Request $request, Shipment $shipment): JsonResponse
    {
        if (Gate::denies('shipments.update')) {
            abort(403, 'No tiene permisos para cancelar envios');
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $shipment = $this->shipmentService->cancelShipment($shipment, $request->reason);

            return response()->json([
                'data' => [
                    'type' => 'shipments',
                    'id' => (string) $shipment->id,
                    'attributes' => [
                        'shipmentNumber' => $shipment->shipment_number,
                        'status' => $shipment->status,
                    ],
                ],
                'meta' => [
                    'message' => 'Envio cancelado.',
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
     * Get shipment summary for an order.
     * GET /api/v1/sales-orders/{order}/shipment-summary
     */
    public function orderSummary(SalesOrder $order): JsonResponse
    {
        if (Gate::denies('shipments.index')) {
            abort(403, 'No tiene permisos para ver resumen de envios');
        }

        $summary = $this->shipmentService->getOrderShipmentSummary($order);

        return response()->json([
            'data' => [
                'type' => 'shipment-summaries',
                'id' => (string) $order->id,
                'attributes' => $summary,
            ],
        ]);
    }
}
