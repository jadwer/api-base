<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;

/**
 * DESIGN_ECOMMERCE_PAGO_STOCK (H-A): disponible = fisico - reservado - comprometido.
 *
 * Comprometido = unidades en ordenes PAGADAS aun no entregadas. El fisico sale
 * del almacen hasta la entrega (diseno del refactor: exit + COGS al entregar),
 * asi que entre el pago y la entrega esas unidades deben restarse de lo
 * vendible o dos compradores pueden pagar el mismo stock (oversell secuencial).
 *
 * Alcance DELIBERADO (decision de Gabino 2026-07-18): solo ordenes con
 * payment_status='paid' comprometen. Las ordenes del dashboard (flujo de
 * cotizacion, historicamente quedan pending eternas) NO: contarlas bloquearia
 * el catalogo con basura historica y ese canal lo controla el staff.
 *
 * No hay expiracion ni liberacion explicita: una orden pagada sale del
 * conjunto sola al entregarse (delivered baja el fisico), al cancelarse o al
 * reembolsarse (payment_status deja de ser paid).
 */
class CommittedStockService
{
    // 'pending' incluido: el checkout del carrito crea la orden en pending (el
    // flujo CheckoutSession la crea en confirmed). Como el filtro duro es
    // payment_status='paid', una pending del dashboard (sin pago) NO cuenta.
    private const COMMITTING_STATUSES = ['pending', 'confirmed', 'processing', 'shipped'];

    /**
     * Unidades comprometidas (pagadas, no entregadas) de un producto.
     */
    public function committedFor(int $productId): float
    {
        return (float) DB::table('sales_order_items')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_order_items.product_id', $productId)
            ->where('sales_orders.payment_status', 'paid')
            ->whereIn('sales_orders.status', self::COMMITTING_STATUSES)
            ->sum('sales_order_items.quantity');
    }

    /**
     * Disponible para vender: fisico - reservado - comprometido, sobre todos
     * los almacenes del producto.
     */
    public function availableForSale(int $productId): float
    {
        $physical = (float) DB::table('stock')
            ->where('product_id', $productId)
            ->sum(DB::raw('quantity - reserved_quantity'));

        return $physical - $this->committedFor($productId);
    }
}
