<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Models\FolioSequence;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * Alta de pedido de venta con sus partidas en una sola transaccion (hallazgo
 * Atomo 22-10): antes el navegador creaba el encabezado en $0, las partidas
 * una por una y al final mandaba los totales; si fallaba una partida quedaba
 * un pedido a medias en $0. Los totales e IVA los calcula el servidor.
 */
class SalesOrderWithItemsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }
        if (!$user->can('sales-orders.store') || !$user->can('sales-order-items.store')) {
            return response()->json(['message' => 'No tiene permisos para crear pedidos de venta.'], 403);
        }

        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'order_number' => ['nullable', 'string', 'max:50', 'unique:sales_orders,order_number'],
            'order_date' => ['required', 'date'],
            'status' => ['required', 'in:draft,pending,confirmed'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'customer_po_number' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'contact_id' => 'cliente',
            'order_number' => 'número de pedido',
            'order_date' => 'fecha del pedido',
            'status' => 'estado',
            'items' => 'partidas',
            'items.*.product_id' => 'producto de la partida :position',
            'items.*.quantity' => 'cantidad de la partida :position',
            'items.*.unit_price' => 'precio unitario de la partida :position',
            'items.*.discount' => 'descuento de la partida :position',
        ]);

        $order = DB::transaction(function () use ($data): SalesOrder {
            $order = SalesOrder::create([
                'contact_id' => $data['contact_id'],
                'order_number' => $data['order_number'] ?? FolioSequence::getNextFolio('sales_order'),
                'order_date' => $data['order_date'],
                // Surtido F4: confirmado se alcanza por la maquina de estados (aparta
                // existencia y genera backorder del faltante), nunca al crear.
                'status' => $data['status'] === 'confirmed' ? 'draft' : $data['status'],
                'notes' => $data['notes'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'customer_po_number' => $data['customer_po_number'] ?? null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
            ]);

            foreach ($data['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                ]);
            }

            $order->recalculateTotals();

            if ($data['status'] === 'confirmed') {
                $order = app(\Modules\Sales\Services\OrderStatusService::class)
                    ->updateStatus($order->fresh(), 'confirmed', 'Confirmado al crear el pedido');
            }

            return $order->fresh();
        });

        return response()->json([
            'message' => 'Pedido creado',
            'data' => [
                'id' => (string) $order->id,
                'order_number' => $order->order_number,
                'subtotal' => (float) $order->subtotal,
                'tax_amount' => (float) $order->tax_amount,
                'total_amount' => (float) $order->total_amount,
            ],
        ], 201);
    }
}
