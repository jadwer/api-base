<?php

namespace Modules\Billing\Tests\Feature;

use Modules\Billing\Services\CFDI\PrefacturaPDFGenerator;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Tests\TestCase;

/**
 * La prefactura desde el pedido leia columnas que no existen: la clave SAT siempre salia
 * generica y el importe de cada partida en $0 (DOC-06, 22-11).
 */
class PrefacturaFromOrderItemsTest extends TestCase
{
    public function test_items_use_the_product_sat_keys_and_the_line_amount(): void
    {
        $order = SalesOrder::factory()->create();
        $withKeys = Product::factory()->create(['sat_clave_prod_serv' => '41116105', 'sat_clave_unidad' => 'LTR']);
        $withoutKeys = Product::factory()->create(['sat_clave_prod_serv' => null, 'sat_clave_unidad' => null]);
        SalesOrderItem::factory()->create(['sales_order_id' => $order->id, 'product_id' => $withKeys->id, 'quantity' => 3, 'unit_price' => 99.99, 'discount' => 0, 'total' => 299.97]);
        SalesOrderItem::factory()->create(['sales_order_id' => $order->id, 'product_id' => $withoutKeys->id, 'quantity' => 1, 'unit_price' => 50, 'discount' => 0, 'total' => 50]);

        $method = new \ReflectionMethod(PrefacturaPDFGenerator::class, 'buildPseudoItems');
        $items = $method->invoke(app(PrefacturaPDFGenerator::class), $order->fresh('items.product'));

        $this->assertSame('41116105', $items[0]->clave_prod_serv);
        $this->assertSame('LTR', $items[0]->clave_unidad);
        $this->assertSame(9999, $items[0]->valor_unitario);
        $this->assertSame(29997, $items[0]->importe);

        $this->assertSame('01010101', $items[1]->clave_prod_serv);
        $this->assertSame('E48', $items[1]->clave_unidad);
        $this->assertSame(5000, $items[1]->importe);
    }
}
