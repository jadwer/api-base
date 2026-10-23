<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;

/**
 * Alta de orden de compra con sus partidas en una sola transaccion (hallazgo
 * Atomo 22-10, mismo patron que SalesOrderWithItemsController). El total lo
 * calcula el servidor; el folio lo asigna el modelo.
 */
class PurchaseOrderWithItemsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }
        if (!$user->hasPermissionTo('purchase-orders.store', 'api') || !$user->hasPermissionTo('purchase-order-items.store', 'api')) {
            return response()->json(['message' => 'No tiene permisos para crear órdenes de compra.'], 403);
        }

        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'order_date' => ['required', 'date'],
            'status' => ['required', 'in:pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'contact_id' => 'proveedor',
            'order_date' => 'fecha de la orden',
            'status' => 'estado',
            'items' => 'partidas',
            'items.*.product_id' => 'producto de la partida :position',
            'items.*.quantity' => 'cantidad de la partida :position',
            'items.*.unit_price' => 'costo unitario de la partida :position',
            'items.*.discount' => 'descuento de la partida :position',
        ]);

        $order = DB::transaction(function () use ($data): PurchaseOrder {
            $order = PurchaseOrder::create([
                'contact_id' => $data['contact_id'],
                'order_date' => $data['order_date'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'total_amount' => 0,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                ]);
            }

            $order->recalculateTotals();

            return $order->fresh();
        });

        return response()->json([
            'message' => 'Orden de compra creada',
            'data' => [
                'id' => (string) $order->id,
                'order_number' => $order->order_number,
                'total_amount' => (float) $order->total_amount,
            ],
        ], 201);
    }
}
