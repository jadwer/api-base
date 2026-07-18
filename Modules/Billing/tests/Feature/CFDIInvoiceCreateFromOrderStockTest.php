<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\CFDIAutomationService;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * Facturar desde orden entregada.
 *
 * La "regla fiscal de stock" de la Fase A fue RETIRADA en c23c5d1: el endpoint
 * solo acepta ordenes delivered y post-refactor el stock salio POR la entrega
 * misma, asi que la regla fallaba siempre (bloqueaba facturar lo ya entregado).
 * La garantia de inventario vive en createExit al entregar, no aqui. Los dos
 * tests que consagraban la regla retirada se eliminaron en la Fase 2.7; el
 * invariante real del ciclo (entrega descuenta stock -> factura -> cobro) lo
 * cubre Modules/Sales/tests/Integration/CycleSaleInvariantTest.
 */
class CFDIInvoiceCreateFromOrderStockTest extends TestCase
{
    protected CompanySetting $settings;
    protected ARInvoice $arInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = CompanySetting::factory()->create([
            'company_name' => 'Test Company S.A. de C.V.',
            'rfc' => 'TCO010101ABC',
            'tax_regime' => '601',
            'postal_code' => '06600',
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: SalesOrder, 1: Product, 2: Contact}
     */
    private function makeDeliveredOrderWithArInvoice(float $quantity = 5): array
    {
        $contact = Contact::factory()->customer()->create([
            'tax_id' => 'XAXX010101000',
        ]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'delivered',
            'subtotal' => $quantity * 100,
            'tax_amount' => $quantity * 16,
            'total_amount' => $quantity * 116,
        ]);

        $product = Product::factory()->create();

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => 100,
            'discount' => 0,
            'total' => $quantity * 100,
        ]);

        $this->arInvoice = ARInvoice::factory()->create([
            'sales_order_id' => $order->id,
            'contact_id' => $contact->id,
            'status' => 'posted',
        ]);

        return [$order, $product, $contact];
    }

    private function giveStock(Product $product, float $quantity): void
    {
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'warehouse_location_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'status' => 'active',
        ]);
    }

    public function test_facturar_proceeds_when_stock_is_sufficient(): void
    {
        $admin = $this->getAdminUser();
        [$order, $product, $contact] = $this->makeDeliveredOrderWithArInvoice(quantity: 5);
        $this->giveStock($product, 10);

        $cfdi = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->settings->id,
            'contact_id' => $contact->id,
            'status' => 'draft',
        ]);

        $this->mock(CFDIAutomationService::class, function ($mock) use ($cfdi) {
            $mock->shouldReceive('generateFromARInvoice')->once()->andReturn($cfdi);
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/facturar");

        $response->assertStatus(201);
        $response->assertJson(['message' => 'CFDI generado exitosamente']);
    }

    public function test_prefactura_from_order_does_not_validate_stock(): void
    {
        $admin = $this->getAdminUser();
        [$order] = $this->makeDeliveredOrderWithArInvoice(quantity: 5);
        // Sin stock: la prefactura NO debe bloquearse

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/sales-orders/{$order->id}/prefactura");

        $response->assertStatus(200);
    }
}
