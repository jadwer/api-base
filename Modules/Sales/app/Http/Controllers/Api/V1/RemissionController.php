<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\RemissionItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\RemissionPDFGenerator;

/**
 * SA-M006: Remission Controller
 *
 * Handles remission (delivery note) operations for sales orders.
 */
class RemissionController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;

    /**
     * Create a remission from a sales order
     * POST /api/v1/remissions/from-order
     */
    public function createFromOrder(Request $request): JsonResponse
    {
        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string|max:500',
            'shipping_address' => 'nullable|array',
            'delivery_notes' => 'nullable|string|max:2000',
            'internal_notes' => 'nullable|string|max:2000',
        ]);

        $order = SalesOrder::with('items.product')->findOrFail($request->input('sales_order_id'));

        return DB::transaction(function () use ($request, $order) {
            // Create remission
            $remission = Remission::create([
                'sales_order_id' => $order->id,
                'shipment_id' => $request->input('shipment_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'remission_date' => now(),
                'shipping_address' => $request->input('shipping_address'),
                'delivery_notes' => $request->input('delivery_notes'),
                'internal_notes' => $request->input('internal_notes'),
            ]);

            // Create remission items
            foreach ($request->input('items') as $itemData) {
                $orderItem = $order->items->firstWhere('id', $itemData['sales_order_item_id']);

                if (!$orderItem) {
                    continue;
                }

                RemissionItem::create([
                    'remission_id' => $remission->id,
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $itemData['quantity'],
                    'product_name' => $orderItem->product?->name,
                    'product_sku' => $orderItem->product?->sku,
                    'unit' => $orderItem->product?->unit ?? 'PZA',
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            return response()->json([
                'data' => $this->transformRemission($remission->fresh(['items', 'salesOrder.contact'])),
                'message' => 'Remission created successfully'
            ], 201);
        });
    }

    /**
     * Create remission with all items from order
     * POST /api/v1/remissions/from-order-full
     */
    public function createFromOrderFull(Request $request): JsonResponse
    {
        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'shipment_id' => 'nullable|exists:shipments,id',
            'shipping_address' => 'nullable|array',
            'delivery_notes' => 'nullable|string|max:2000',
            'internal_notes' => 'nullable|string|max:2000',
        ]);

        $order = SalesOrder::with('items.product')->findOrFail($request->input('sales_order_id'));

        if ($order->items->isEmpty()) {
            return response()->json([
                'error' => 'Cannot create remission from order with no items'
            ], 400);
        }

        return DB::transaction(function () use ($request, $order) {
            // Create remission
            $remission = Remission::create([
                'sales_order_id' => $order->id,
                'shipment_id' => $request->input('shipment_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'remission_date' => now(),
                'shipping_address' => $request->input('shipping_address') ?? $order->shipping_address,
                'delivery_notes' => $request->input('delivery_notes'),
                'internal_notes' => $request->input('internal_notes'),
            ]);

            // Create remission items from all order items
            foreach ($order->items as $orderItem) {
                RemissionItem::create([
                    'remission_id' => $remission->id,
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'product_name' => $orderItem->product?->name,
                    'product_sku' => $orderItem->product?->sku,
                    'unit' => $orderItem->product?->unit ?? 'PZA',
                ]);
            }

            return response()->json([
                'data' => $this->transformRemission($remission->fresh(['items', 'salesOrder.contact'])),
                'message' => 'Full remission created successfully from order'
            ], 201);
        });
    }

    /**
     * Mark remission as printed and generate PDF
     * POST /api/v1/remissions/{remission}/print
     */
    public function print(Remission $remission, RemissionPDFGenerator $generator): JsonResponse
    {
        if (!$remission->canBePrinted()) {
            return response()->json([
                'error' => 'Remission cannot be printed. It must have items and be in draft or printed status.'
            ], 400);
        }

        $path = $generator->generate($remission);
        $remission->markAsPrinted();

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => asset('storage/' . $path),
            ],
            'message' => 'Remission marked as printed'
        ]);
    }

    /**
     * Mark remission as delivered
     * POST /api/v1/remissions/{remission}/deliver
     */
    public function deliver(Request $request, Remission $remission): JsonResponse
    {
        $request->validate([
            'received_by' => 'nullable|string|max:200',
            'delivery_notes' => 'nullable|string|max:2000',
        ]);

        if (!$remission->canBeDelivered()) {
            return response()->json([
                'error' => 'Remission cannot be marked as delivered. It must be in printed status.'
            ], 400);
        }

        $remission->markAsDelivered(
            $request->input('received_by'),
            $request->input('delivery_notes')
        );

        return response()->json([
            'data' => $this->transformRemission($remission->fresh()),
            'message' => 'Remission marked as delivered'
        ]);
    }

    /**
     * Cancel remission
     * POST /api/v1/remissions/{remission}/cancel
     */
    public function cancel(Remission $remission): JsonResponse
    {
        if (!$remission->cancel()) {
            return response()->json([
                'error' => 'This remission cannot be cancelled. Delivered remissions cannot be cancelled.'
            ], 400);
        }

        return response()->json([
            'data' => $this->transformRemission($remission->fresh()),
            'message' => 'Remission cancelled successfully'
        ]);
    }

    /**
     * Get remissions for a sales order
     * GET /api/v1/sales-orders/{order}/remissions
     */
    public function forOrder(SalesOrder $order): JsonResponse
    {
        $remissions = $order->remissions()
            ->with(['items', 'warehouse'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $remissions->map(fn($r) => $this->transformRemission($r)),
            'meta' => [
                'count' => $remissions->count(),
                'order_number' => $order->order_number,
            ]
        ]);
    }

    /**
     * Generate PDF for remission
     * GET /api/v1/remissions/{remission}/pdf
     */
    public function generatePdf(Remission $remission, RemissionPDFGenerator $generator): JsonResponse
    {
        $path = $generator->generate($remission);

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => asset('storage/' . $path),
            ],
            'message' => 'PDF generated successfully'
        ]);
    }

    /**
     * Download remission PDF
     * GET /api/v1/remissions/{remission}/pdf/download
     */
    public function downloadPdf(Remission $remission, RemissionPDFGenerator $generator)
    {
        return $generator->download($remission);
    }

    /**
     * Preview remission PDF (inline)
     * GET /api/v1/remissions/{remission}/pdf/preview
     */
    public function previewPdf(Remission $remission, RemissionPDFGenerator $generator)
    {
        return $generator->preview($remission);
    }

    /**
     * Stream remission PDF (real-time generation)
     * GET /api/v1/remissions/{remission}/pdf/stream
     */
    public function streamPdf(Remission $remission, RemissionPDFGenerator $generator)
    {
        return $generator->stream($remission);
    }

    /**
     * Get remissions summary/statistics
     * GET /api/v1/remissions/summary
     */
    public function summary(): JsonResponse
    {
        $stats = [
            'total' => Remission::count(),
            'draft' => Remission::draft()->count(),
            'printed' => Remission::printed()->count(),
            'delivered' => Remission::delivered()->count(),
            'cancelled' => Remission::byStatus('cancelled')->count(),
            'today' => Remission::whereDate('remission_date', today())->count(),
            'pending_delivery' => Remission::printed()->count(),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Transform remission to JSON:API-like format
     */
    private function transformRemission(Remission $remission): array
    {
        return [
            'type' => 'remissions',
            'id' => (string) $remission->id,
            'attributes' => [
                'remissionNumber' => $remission->remission_number,
                'salesOrderId' => $remission->sales_order_id,
                'shipmentId' => $remission->shipment_id,
                'warehouseId' => $remission->warehouse_id,
                'status' => $remission->status,
                'remissionDate' => $remission->remission_date?->toISOString(),
                'deliveryDate' => $remission->delivery_date?->toISOString(),
                'deliveredBy' => $remission->delivered_by,
                'receivedBy' => $remission->received_by,
                'deliveryNotes' => $remission->delivery_notes,
                'shippingAddress' => $remission->shipping_address,
                'pdfPath' => $remission->pdf_path,
                'pdfGeneratedAt' => $remission->pdf_generated_at?->toISOString(),
                'internalNotes' => $remission->internal_notes,
                'totalItems' => $remission->totalItems,
                'totalQuantity' => $remission->totalQuantity,
                'isEditable' => $remission->isEditable(),
                'canBePrinted' => $remission->canBePrinted(),
                'canBeDelivered' => $remission->canBeDelivered(),
                'createdAt' => $remission->created_at?->toISOString(),
                'updatedAt' => $remission->updated_at?->toISOString(),
            ],
            'relationships' => [
                'salesOrder' => $remission->relationLoaded('salesOrder') && $remission->salesOrder ? [
                    'data' => [
                        'type' => 'sales-orders',
                        'id' => (string) $remission->salesOrder->id,
                    ]
                ] : null,
                'items' => $remission->relationLoaded('items') ? [
                    'data' => $remission->items->map(fn($item) => [
                        'type' => 'remission-items',
                        'id' => (string) $item->id,
                        'attributes' => [
                            'quantity' => $item->quantity,
                            'productName' => $item->product_name,
                            'productSku' => $item->product_sku,
                            'unit' => $item->unit,
                            'batchNumber' => $item->batch_number,
                            'expiryDate' => $item->expiry_date?->toISOString(),
                            'notes' => $item->notes,
                        ]
                    ])->toArray()
                ] : null,
            ]
        ];
    }
}
