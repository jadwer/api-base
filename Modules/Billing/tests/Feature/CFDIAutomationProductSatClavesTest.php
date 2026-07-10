<?php

namespace Modules\Billing\Tests\Feature;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\CFDI\CFDIXMLGenerator;
use Modules\Billing\Services\CFDIAutomationService;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Tests\TestCase;

/**
 * El CFDI automatico (facturar orden / post-pago Stripe) debe crear los
 * CFDIItems desde los items de la orden de venta ligada al ARInvoice,
 * propagando product_id y tomando snapshot de las claves SAT configuradas
 * en el producto (sat_clave_prod_serv / sat_clave_unidad). Si el producto
 * no tiene claves, se conservan las genericas (01010101 / E48).
 */
class CFDIAutomationProductSatClavesTest extends TestCase
{
    protected CompanySetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        // El servicio usa la primera CompanySetting activa; desactivar las seeded
        CompanySetting::query()->update(['is_active' => false]);

        $this->settings = CompanySetting::factory()->create([
            'company_name' => 'Test Company S.A. de C.V.',
            'rfc' => 'TCO010101ABC',
            'tax_regime' => '601',
            'postal_code' => '06600',
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: SalesOrder, 1: ARInvoice}
     */
    private function makeDeliveredOrderWithArInvoice(Product $product, float $quantity = 2): array
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

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => 100,
            'discount' => 0,
            'total' => $quantity * 100,
        ]);

        $arInvoice = ARInvoice::factory()->create([
            'sales_order_id' => $order->id,
            'contact_id' => $contact->id,
            'status' => 'posted',
            'subtotal' => $quantity * 100,
            'tax_amount' => $quantity * 16,
            'total_amount' => $quantity * 116,
        ]);

        return [$order, $arInvoice];
    }

    public function test_automatic_cfdi_snapshots_product_sat_claves_and_product_id(): void
    {
        $product = Product::factory()->create([
            'sat_clave_prod_serv' => '12352301',
            'sat_clave_unidad' => 'LTR',
        ]);

        [, $arInvoice] = $this->makeDeliveredOrderWithArInvoice($product);

        $cfdi = app(CFDIAutomationService::class)->generateFromARInvoice($arInvoice);

        $items = CFDIItem::where('cfdi_invoice_id', $cfdi->id)->get();
        $this->assertCount(1, $items);

        $item = $items->first();
        $this->assertEquals($product->id, $item->product_id);
        $this->assertEquals('12352301', $item->clave_prod_serv);
        $this->assertEquals('LTR', $item->clave_unidad);
        $this->assertEquals($product->sku, $item->no_identificacion);
        $this->assertEquals(2.0, (float) $item->cantidad);
        $this->assertEquals(10000, $item->valor_unitario); // 100.00 en centavos
        $this->assertEquals(20000, $item->importe);

        $xml = (new CFDIXMLGenerator())->generate($cfdi->fresh('items'));

        $this->assertStringContainsString('ClaveProdServ="12352301"', $xml);
        $this->assertStringContainsString('ClaveUnidad="LTR"', $xml);
    }

    public function test_automatic_cfdi_keeps_generic_claves_when_product_has_none(): void
    {
        $product = Product::factory()->create([
            'sat_clave_prod_serv' => null,
            'sat_clave_unidad' => null,
        ]);

        [, $arInvoice] = $this->makeDeliveredOrderWithArInvoice($product);

        $cfdi = app(CFDIAutomationService::class)->generateFromARInvoice($arInvoice);

        $item = CFDIItem::where('cfdi_invoice_id', $cfdi->id)->firstOrFail();
        $this->assertEquals($product->id, $item->product_id);
        $this->assertEquals('01010101', $item->clave_prod_serv);
        $this->assertEquals('E48', $item->clave_unidad);

        $xml = (new CFDIXMLGenerator())->generate($cfdi->fresh('items'));

        $this->assertStringContainsString('ClaveProdServ="01010101"', $xml);
        $this->assertStringContainsString('ClaveUnidad="E48"', $xml);
    }

    public function test_ar_invoice_without_sales_order_creates_cfdi_without_items(): void
    {
        $contact = Contact::factory()->customer()->create([
            'tax_id' => 'XEXX010101000',
        ]);

        $arInvoice = ARInvoice::factory()->create([
            'contact_id' => $contact->id,
            'sales_order_id' => null,
            'status' => 'posted',
            'subtotal' => 100.00,
            'tax_amount' => 16.00,
            'total_amount' => 116.00,
        ]);

        $cfdi = app(CFDIAutomationService::class)->generateFromARInvoice($arInvoice);

        $this->assertEquals(0, CFDIItem::where('cfdi_invoice_id', $cfdi->id)->count());
    }

    public function test_facturar_endpoint_creates_cfdi_items_with_product_sat_claves(): void
    {
        $admin = $this->getAdminUser();

        $product = Product::factory()->create([
            'sat_clave_prod_serv' => '43231500',
            'sat_clave_unidad' => 'H87',
        ]);

        [$order] = $this->makeDeliveredOrderWithArInvoice($product, quantity: 3);

        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'warehouse_location_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/facturar");

        $response->assertStatus(201);

        $cfdi = CFDIInvoice::findOrFail($response->json('data.id'));
        $item = CFDIItem::where('cfdi_invoice_id', $cfdi->id)->firstOrFail();

        $this->assertEquals($product->id, $item->product_id);
        $this->assertEquals('43231500', $item->clave_prod_serv);
        $this->assertEquals('H87', $item->clave_unidad);
    }
}
