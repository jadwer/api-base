<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\RemissionItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\RemissionPDFGenerator;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Services\InventoryMovementService;

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
        if (Gate::denies('remissions.store')) {
            abort(403, 'No tiene permisos para crear remisiones');
        }

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

        // Validate stock availability
        $insufficientItems = [];
        foreach ($request->input('items') as $itemData) {
            $orderItem = $order->items->firstWhere('id', $itemData['sales_order_item_id']);
            if ($orderItem) {
                $available = Stock::where('product_id', $orderItem->product_id)
                    ->sum('available_quantity');
                if ($available < $itemData['quantity']) {
                    $insufficientItems[] = [
                        'product_id' => $orderItem->product_id,
                        'product_name' => $orderItem->product?->name ?? 'Producto',
                        'sku' => $orderItem->product?->sku ?? '',
                        'required' => (float) $itemData['quantity'],
                        'available' => (float) $available,
                        'deficit' => (float) ($itemData['quantity'] - $available),
                    ];
                }
            }
        }

        if (!empty($insufficientItems)) {
            return response()->json([
                'error' => 'Stock insuficiente para generar la remision',
                'insufficient_items' => $insufficientItems,
                'suggestion' => 'Genere una Orden de Compra para reponer el inventario antes de crear la remision.',
            ], 422);
        }

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
                    throw new \InvalidArgumentException(
                        "Item {$itemData['sales_order_item_id']} does not belong to order {$order->id}"
                    );
                }

                // Validate quantity does not exceed remaining
                $alreadyRemitted = RemissionItem::where('sales_order_item_id', $orderItem->id)
                    ->whereHas('remission', fn($q) => $q->where('status', '!=', 'cancelled'))
                    ->sum('quantity');
                $remaining = $orderItem->quantity - $alreadyRemitted;

                if ($itemData['quantity'] > $remaining) {
                    throw new \InvalidArgumentException(
                        "Quantity {$itemData['quantity']} exceeds remaining {$remaining} for item {$orderItem->id}"
                    );
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
        if (Gate::denies('remissions.store')) {
            abort(403, 'No tiene permisos para crear remisiones');
        }

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

        // Validate stock availability before creating remission
        $insufficientItems = [];
        foreach ($order->items as $orderItem) {
            $available = Stock::where('product_id', $orderItem->product_id)
                ->sum('available_quantity');
            if ($available < $orderItem->quantity) {
                $insufficientItems[] = [
                    'product_id' => $orderItem->product_id,
                    'product_name' => $orderItem->product?->name ?? 'Producto',
                    'sku' => $orderItem->product?->sku ?? '',
                    'required' => (float) $orderItem->quantity,
                    'available' => (float) $available,
                    'deficit' => (float) ($orderItem->quantity - $available),
                ];
            }
        }

        if (!empty($insufficientItems)) {
            return response()->json([
                'error' => 'Stock insuficiente para generar la remision',
                'insufficient_items' => $insufficientItems,
                'suggestion' => 'Genere una Orden de Compra para reponer el inventario antes de crear la remision.',
            ], 422);
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
        if (Gate::denies('remissions.update')) {
            abort(403, 'No tiene permisos para imprimir remisiones');
        }

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
        if (Gate::denies('remissions.update')) {
            abort(403, 'No tiene permisos para marcar remisiones como entregadas');
        }

        $request->validate([
            'received_by' => 'nullable|string|max:200',
            'delivery_notes' => 'nullable|string|max:2000',
        ]);

        if (!$remission->canBeDelivered()) {
            return response()->json([
                'error' => 'Remission cannot be marked as delivered. It must be in printed status.'
            ], 400);
        }

        // QA post-commit (C2): los shipments descuentan Stock.quantity DIRECTO (sin
        // movimiento ni COGS) y la remision descuenta via createExit. Si la orden ya
        // desconto por shipments, entregar la remision descontaria DOS veces. Regla de
        // negocio puente: no mezclar flujos (la unificacion del camino de salida es R9
        // del diseno de arquitectura).
        $orderForGuard = $remission->salesOrder;
        if ($orderForGuard && $orderForGuard->shipments()->whereIn('status', ['shipped', 'delivered'])->exists()) {
            return response()->json([
                'error' => 'Esta orden ya descuenta inventario por envios (shipments). '
                    . 'No se puede entregar tambien por remision: use un solo flujo de salida.',
            ], 422);
        }

        // Refactor ciclo (Patron 3, C1 + 5b/F3): la entrega DESCUENTA stock real y todo
        // el paso (descuento + markAsDelivered + update de la orden) va en UNA transaccion
        // externa atomica. Antes corrian sueltos: un fallo entre el commit del exit y el
        // update dejaba stock descontado sin entrega registrada. El evento de dominio
        // SalesOrderDelivered se dispara DESPUES del commit (fuera de la transaccion) para
        // que un fallo al crear la ARInvoice no revierta la entrega (el listener maneja su
        // propio error). Cada item genera un 'exit' que descuenta Stock.quantity y postea
        // el COGS (DR 5101 / CR 1108). Idempotente por reference_type='remission'+item.
        $shouldFireDelivered = false;
        try {
            $salesOrder = DB::transaction(function () use ($remission, $request, &$shouldFireDelivered) {
                $this->createExitMovementsForRemission($remission, $request->user()?->id);

                $remission->markAsDelivered(
                    $request->input('received_by'),
                    $request->input('delivery_notes')
                );

                $salesOrder = $remission->salesOrder;
                if ($salesOrder && $salesOrder->status !== 'delivered' && $salesOrder->status !== 'cancelled') {
                    $hasNonDeliveredRemissions = $salesOrder->remissions()
                        ->where('status', '!=', 'cancelled')
                        ->where('status', '!=', 'delivered')
                        ->exists();

                    $hasDeliveredRemissions = $salesOrder->remissions()
                        ->where('status', 'delivered')
                        ->exists();

                    if (!$hasNonDeliveredRemissions && $hasDeliveredRemissions) {
                        $salesOrder->update(['status' => 'delivered']);
                        $shouldFireDelivered = true;
                    }
                }

                return $salesOrder;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'No se pudo completar la entrega de la remision: ' . $e->getMessage(),
            ], 422);
        }

        // Fuera de la transaccion: disparar el evento de facturacion solo si la orden
        // paso a delivered. El listener crea la ARInvoice y maneja su propio error.
        if ($shouldFireDelivered && $salesOrder) {
            event(new \Modules\Sales\Events\SalesOrderDelivered($salesOrder->fresh()));
        }

        return response()->json([
            'data' => $this->transformRemission($remission->fresh()),
            'message' => 'Remission marked as delivered'
        ]);
    }

    /**
     * Refactor ciclo (Patron 3, C1): crea el movimiento de salida de inventario por cada
     * item de la remision al entregarla. Descuenta Stock.quantity y dispara el asiento
     * COGS via InventoryMovementCreated.
     *
     * Idempotencia (5b/F7 + R1): por LINEA de remision via idempotency_key
     * "remission:{id}:item:{itemId}" con UNIQUE en BD. Antes se chequeaba por
     * product_id (dos lineas del mismo producto sub-descontaban) y luego por
     * whereJsonContains (fragil por driver); la clave natural resuelve ambos.
     *
     * NO abre transaccion propia: el metodo deliver() lo envuelve en una transaccion
     * externa (5b/F3) junto con markAsDelivered y el update de la orden.
     *
     * Warehouse: usa el de la remision si existe; si no, el warehouse del stock del
     * producto (donde realmente hay existencias); como ultimo recurso el primer almacen.
     *
     * @throws \Throwable si algun item no tiene stock suficiente (createExit lanza) ->
     *         la transaccion externa del deliver revierte todo.
     */
    private function createExitMovementsForRemission(Remission $remission, ?int $userId): void
    {
        $service = app(InventoryMovementService::class);

        $remission->loadMissing('items');

        foreach ($remission->items as $item) {
            if (!$item->product_id || $item->quantity <= 0) {
                continue;
            }

            // R1: la idempotencia por linea vive en la idempotency_key (UNIQUE en BD)
            // que se pasa a createExit; el whereJsonContains anterior era fragil por
            // driver y tenia ventana de carrera.

            // Resolver el warehouse: remision -> stock del producto -> primer almacen.
            $warehouseId = $remission->warehouse_id;
            if (!$warehouseId) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->orderByDesc('quantity')
                    ->first();
                $warehouseId = $stock?->warehouse_id
                    ?? \Modules\Inventory\Models\Warehouse::query()->value('id');
            }

            if (!$warehouseId) {
                throw new \RuntimeException("No hay almacen para descontar el producto {$item->product_id}");
            }

            $service->createExit([
                'product_id' => $item->product_id,
                'warehouse_id' => $warehouseId,
                'quantity' => $item->quantity,
                'movement_date' => now(),
                'reference_type' => 'remission',
                'reference_id' => $remission->id,
                'idempotency_key' => "remission:{$remission->id}:item:{$item->id}",
                'description' => "Salida por remision {$remission->remission_number}",
                'user_id' => $userId,
                // QA post-commit (A2): fallback de costo para el COGS cuando el stock
                // no trae unit_cost real (createExit prioriza el costo del stock).
                'unit_cost' => (float) (\Modules\Product\Models\Product::find($item->product_id)?->cost ?? 0),
                'metadata' => ['remission_item_id' => $item->id],
                // R4: la exencion de IV-009 vive en la REGLA (reference_type='remission'
                // esta en QUALITY_EXEMPT_REFERENCE_TYPES), no en flags por call-site.
            ]);
        }
    }

    /**
     * Cancel remission
     * POST /api/v1/remissions/{remission}/cancel
     */
    public function cancel(Remission $remission): JsonResponse
    {
        if (Gate::denies('remissions.update')) {
            abort(403, 'No tiene permisos para cancelar remisiones');
        }

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
        if (Gate::denies('remissions.index')) {
            abort(403, 'No tiene permisos para ver remisiones');
        }

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
        if (Gate::denies('remissions.show')) {
            abort(403, 'No tiene permisos para generar PDF de remisiones');
        }

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
        if (Gate::denies('remissions.show')) {
            abort(403, 'No tiene permisos para descargar PDF de remisiones');
        }

        return $generator->download($remission);
    }

    /**
     * Preview remission PDF (inline)
     * GET /api/v1/remissions/{remission}/pdf/preview
     */
    public function previewPdf(Remission $remission, RemissionPDFGenerator $generator)
    {
        if (Gate::denies('remissions.show')) {
            abort(403, 'No tiene permisos para previsualizar remisiones');
        }

        return $generator->preview($remission);
    }

    /**
     * Stream remission PDF (real-time generation)
     * GET /api/v1/remissions/{remission}/pdf/stream
     */
    public function streamPdf(Remission $remission, RemissionPDFGenerator $generator)
    {
        if (Gate::denies('remissions.show')) {
            abort(403, 'No tiene permisos para ver remisiones');
        }

        return $generator->stream($remission);
    }

    /**
     * Get remissions summary/statistics
     * GET /api/v1/remissions/summary
     */
    public function summary(): JsonResponse
    {
        if (Gate::denies('remissions.index')) {
            abort(403, 'No tiene permisos para ver resumen de remisiones');
        }

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
