<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CompanySetting;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;

/**
 * SA-M011: Tests for Prefactura (Invoice Preview) functionality
 */
class PrefacturaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected CompanySetting $settings;
    protected Contact $contact;
    protected SalesOrder $order;

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

        $this->contact = Contact::factory()->create([
            'name' => 'Cliente de Prueba',
            'legal_name' => 'Cliente de Prueba S.A. de C.V.',
            'tax_id' => 'XAXX010101000',
            'is_customer' => true,
        ]);

        $product = Product::factory()->create([
            'name' => 'Producto de Prueba',
            'sku' => 'TEST-001',
        ]);

        $this->order = SalesOrder::factory()->create([
            'contact_id' => $this->contact->id,
            'subtotal' => 1000.00,
            'tax_amount' => 160.00,
            'total_amount' => 1160.00,
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $this->order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500.00,
            'discount' => 0.00,
            'total' => 1000.00,
        ]);
    }

    /** @test */
    public function it_can_generate_prefactura_from_cfdi_invoice(): void
    {
        $admin = $this->getAdminUser();

        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->settings->id,
            'contact_id' => $this->contact->id,
            'series' => 'F',
            'folio' => 1,
            'status' => 'draft',
            'receptor_nombre' => $this->contact->legal_name,
            'receptor_rfc' => $this->contact->tax_id,
            'receptor_uso_cfdi' => 'G03',
            'tipo_comprobante' => 'I',
            'metodo_pago' => 'PUE',
            'fecha_emision' => now(),
            'total' => 116000, // cents
            'uuid' => null,
        ]);

        CFDIItem::factory()->create([
            'cfdi_invoice_id' => $invoice->id,
            'descripcion' => 'Producto de prueba',
            'cantidad' => 2,
            'valor_unitario' => 50000,
            'importe' => 100000,
            'clave_prod_serv' => '01010101',
            'clave_unidad' => 'H87',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/cfdi-invoices/{$invoice->id}/prefactura");

        $response->assertStatus(200);
        $response->assertJsonPath('data.folio', $invoice->getFolioCompleto());
        $response->assertJsonPath('data.is_stamped', false);
    }

    /** @test */
    public function it_can_download_prefactura_pdf(): void
    {
        $admin = $this->getAdminUser();

        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->settings->id,
            'contact_id' => $this->contact->id,
            'series' => 'F',
            'folio' => 1,
            'status' => 'draft',
            'receptor_nombre' => $this->contact->legal_name,
            'receptor_rfc' => $this->contact->tax_id,
            'receptor_uso_cfdi' => 'G03',
            'tipo_comprobante' => 'I',
            'metodo_pago' => 'PUE',
            'fecha_emision' => now(),
            'total' => 116000,
        ]);

        CFDIItem::factory()->create([
            'cfdi_invoice_id' => $invoice->id,
            'descripcion' => 'Producto de prueba',
            'cantidad' => 1,
            'valor_unitario' => 100000,
            'importe' => 100000,
            'clave_prod_serv' => '01010101',
            'clave_unidad' => 'H87',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/cfdi-invoices/{$invoice->id}/prefactura/download");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function it_can_preview_prefactura_inline(): void
    {
        $admin = $this->getAdminUser();

        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->settings->id,
            'contact_id' => $this->contact->id,
            'series' => 'F',
            'folio' => 1,
            'status' => 'draft',
            'receptor_nombre' => $this->contact->legal_name,
            'receptor_rfc' => $this->contact->tax_id,
            'receptor_uso_cfdi' => 'G03',
            'tipo_comprobante' => 'I',
            'metodo_pago' => 'PUE',
            'fecha_emision' => now(),
            'total' => 116000,
        ]);

        CFDIItem::factory()->create([
            'cfdi_invoice_id' => $invoice->id,
            'descripcion' => 'Producto',
            'cantidad' => 1,
            'valor_unitario' => 100000,
            'importe' => 100000,
            'clave_prod_serv' => '01010101',
            'clave_unidad' => 'H87',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/cfdi-invoices/{$invoice->id}/prefactura/preview");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    /** @test */
    public function it_can_generate_prefactura_from_sales_order(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/sales-orders/{$this->order->id}/prefactura");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function it_can_generate_prefactura_with_custom_cfdi_data(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/sales-orders/{$this->order->id}/prefactura?" . http_build_query([
                'receptor_rfc' => 'XEXX010101000',
                'receptor_uso_cfdi' => 'P01',
                'metodo_pago' => 'PPD',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function prefactura_requires_authentication(): void
    {
        $invoice = CFDIInvoice::factory()->create([
            'company_setting_id' => $this->settings->id,
            'contact_id' => $this->contact->id,
        ]);

        $response = $this->getJson("/api/v1/cfdi-invoices/{$invoice->id}/prefactura");

        $response->assertStatus(401);
    }

    /** @test */
    public function prefactura_from_order_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/sales-orders/{$this->order->id}/prefactura");

        $response->assertStatus(401);
    }
}
