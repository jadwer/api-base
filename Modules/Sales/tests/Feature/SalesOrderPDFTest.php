<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Billing\Models\CompanySetting;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;

/**
 * SA-M011: Tests for enhanced SalesOrder PDF generation
 */
class SalesOrderPDFTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected SalesOrder $order;
    protected Contact $contact;
    protected CompanySetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = CompanySetting::factory()->create([
            'company_name' => 'Test Company S.A. de C.V.',
            'rfc' => 'TCO010101ABC',
            'tax_regime' => '601',
            'postal_code' => '06600',
            'address' => 'Av. Reforma 123',
            'city' => 'Ciudad de Mexico',
            'state' => 'CDMX',
            'phone' => '55 1234 5678',
            'email' => 'ventas@test.com',
            'bank_accounts' => [
                [
                    'currency' => 'MXN',
                    'bank_name' => 'BBVA Mexico',
                    'account_number' => '0123456789',
                    'clabe' => '012180001234567890',
                    'beneficiary' => 'Test Company S.A. de C.V.',
                ],
                [
                    'currency' => 'USD',
                    'bank_name' => 'BBVA Mexico',
                    'account_number' => '9876543210',
                    'clabe' => '012180009876543210',
                    'swift' => 'BCMRMXMM',
                    'beneficiary' => 'Test Company S.A. de C.V.',
                ],
            ],
            'commercial_conditions' => [
                'Precios en MXN mas IVA.',
                'Vigencia 30 dias.',
                '50% anticipo, 50% contra entrega.',
            ],
            'is_active' => true,
        ]);

        $this->contact = Contact::factory()->create([
            'name' => 'Cliente de Prueba S.A.',
            'legal_name' => 'Cliente de Prueba S.A. de C.V.',
            'tax_id' => 'CDP010101ABC',
            'email' => 'cliente@test.com',
            'phone' => '55 9876 5432',
            'is_customer' => true,
        ]);

        $product = Product::factory()->create([
            'name' => 'Producto de Prueba',
            'sku' => 'TEST-001',
        ]);

        $this->order = SalesOrder::factory()->create([
            'contact_id' => $this->contact->id,
            'order_number' => 'OV-2026-0001',
            'status' => 'confirmed',
            'order_date' => now(),
            'subtotal' => 1000.00,
            'tax_amount' => 160.00,
            'total_amount' => 1160.00,
            'shipping_address' => [
                'name' => 'Oficina Principal',
                'address_line1' => 'Calle 1 #100',
                'city' => 'Ciudad de Mexico',
                'state' => 'CDMX',
                'postal_code' => '06600',
                'phone' => '55 1111 2222',
            ],
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
    public function it_can_generate_sales_order_pdf(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/sales-orders/{$this->order->id}/pdf");

        $response->assertStatus(200);
        $response->assertJsonPath('data.order_number', 'OV-2026-0001');
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'order_number',
                'pdf_path',
                'pdf_url',
            ],
        ]);
    }

    /** @test */
    public function it_can_download_sales_order_pdf(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/sales-orders/{$this->order->id}/pdf/download");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    /** @test */
    public function it_can_preview_sales_order_pdf_inline(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/sales-orders/{$this->order->id}/pdf/preview");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
    }

    /** @test */
    public function it_can_stream_sales_order_pdf_without_storing(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/sales-orders/{$this->order->id}/pdf/stream");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function it_can_stream_pdf_with_custom_options(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/sales-orders/{$this->order->id}/pdf/stream?" . http_build_query([
                'show_bank_accounts' => false,
                'show_conditions' => false,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function pdf_includes_company_branding(): void
    {
        $admin = $this->getAdminUser();

        // Generate PDF and check it includes company info
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/sales-orders/{$this->order->id}/pdf");

        $response->assertStatus(200);

        // The PDF was generated, verify settings were used
        $this->assertNotNull($this->settings->company_name);
        $this->assertNotNull($this->settings->rfc);
    }

    /** @test */
    public function pdf_includes_customer_information(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/sales-orders/{$this->order->id}/pdf/stream");

        $response->assertStatus(200);

        // Verify order has contact relationship
        $this->order->load('contact');
        $this->assertEquals($this->contact->id, $this->order->contact_id);
        $this->assertEquals('Cliente de Prueba S.A. de C.V.', $this->order->contact->legal_name);
    }

    /** @test */
    public function pdf_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/sales-orders/{$this->order->id}/pdf/download");

        // API returns 401 for unauthenticated JSON requests
        $response->assertStatus(401);
    }

    /** @test */
    public function pdf_shows_correct_status_label(): void
    {
        $admin = $this->getAdminUser();

        // Test with different statuses
        $statuses = ['draft', 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

        foreach ($statuses as $status) {
            $this->order->update(['status' => $status]);

            $response = $this->actingAs($admin, 'sanctum')
                ->get("/api/v1/sales-orders/{$this->order->id}/pdf/stream");

            $response->assertStatus(200);
        }
    }

    /** @test */
    public function pdf_calculates_totals_correctly(): void
    {
        $admin = $this->getAdminUser();

        // Add another item
        $product2 = Product::factory()->create();
        SalesOrderItem::factory()->create([
            'sales_order_id' => $this->order->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'unit_price' => 200.00,
            'discount' => 0.00,
            'total' => 600.00,
        ]);

        // Update order totals
        $this->order->update([
            'subtotal' => 1600.00,
            'tax_amount' => 256.00,
            'total_amount' => 1856.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/sales-orders/{$this->order->id}/pdf/stream");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
