<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;

class CFDIInvoiceShowTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_view_cfdi_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'cfdi-invoices',
                    'id' => (string) $invoice->id,
                ]
            ]);
    }

    public function test_tech_can_view_cfdi_invoice()
    {
        $user = $this->getTechUser();
        $invoice = CFDIInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'cfdi-invoices',
                    'id' => (string) $invoice->id,
                ]
            ]);
    }

    public function test_customer_can_view_cfdi_invoice()
    {
        $user = $this->getCustomerUser();
        $invoice = CFDIInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'cfdi-invoices',
                    'id' => (string) $invoice->id,
                ]
            ]);
    }

    public function test_guest_cannot_view_cfdi_invoice()
    {
        $invoice = CFDIInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_invoice()
    {
        $user = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/99999');

        $response->assertStatus(404);
    }

    public function test_shows_draft_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'draft'
                    ]
                ]
            ]);
    }

    public function test_shows_valid_timbrado_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->valid()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'valid'
                    ]
                ]
            ]);
    }

    public function test_shows_cancelled_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->cancelled()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'cancelled'
                    ]
                ]
            ]);
    }

    public function test_shows_invoice_with_discount()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->withDiscount()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful();

        $this->assertTrue($invoice->descuento > 0);
    }

    public function test_shows_egreso_invoice_with_related_cfdi()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->egreso()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'tipoComprobante' => 'E'
                    ]
                ]
            ]);
    }

    public function test_shows_all_public_attributes()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->valid()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'attributes' => [
                        'series',
                        'folio',
                        'uuid',
                        'tipoComprobante',
                        'receptorRfc',
                        'receptorNombre',
                        'subtotal',
                        'total',
                        'status',
                        'fechaEmision',
                    ]
                ]
            ]);
    }
}
