<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;

class CFDIInvoiceUpdateTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_update_cfdi_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'receptorNombre' => 'Updated Cliente Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'receptorNombre' => 'Updated Cliente Name'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('cfdi_invoices', [
            'id' => $invoice->id,
            'receptor_nombre' => 'Updated Cliente Name',
        ]);
    }

    public function test_tech_cannot_update_cfdi_invoice()
    {
        $user = $this->getTechUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'receptorNombre' => 'Updated Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_update_cfdi_invoice()
    {
        $user = $this->getCustomerUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'receptorNombre' => 'Updated Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_cfdi_invoice()
    {
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'receptorNombre' => 'Updated Name',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertStatus(401);
    }

    public function test_can_update_receptor_info()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'receptorRfc' => 'NEWR010101ABC',
                'receptorNombre' => 'New Receptor Name',
                'receptorUsoCfdi' => 'P01',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful();
    }

    public function test_can_update_amounts()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'subtotal' => 200000,
                'iva' => 32000,
                'total' => 232000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful();
    }

    public function test_can_update_payment_method()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'metodoPago' => 'PPD',
                'condicionesPago' => 'Pago en 60 días',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'metodoPago' => 'PPD'
                    ]
                ]
            ]);
    }

    public function test_status_cannot_be_changed_via_patch()
    {
        // Refactor ciclo (Patron 1): este test verificaba el BUG (un PATCH podia poner
        // status='valid' fingiendo una factura timbrada sin UUID ni sello del PAC).
        // El invariante nuevo es el inverso: el status del CFDI solo cambia por
        // stamp()/cancel() de CFDIStampingService; el PATCH lo IGNORA.
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'status' => 'valid',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'status' => 'draft'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('cfdi_invoices', [
            'id' => $invoice->id,
            'status' => 'draft',
        ]);
    }

    public function test_can_add_discount()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create([
            'subtotal' => 100000,
            'descuento' => 0,
        ]);

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'descuento' => 10000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful();
    }

    public function test_can_update_metadata()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'id' => (string) $invoice->id,
            'attributes' => [
                'metadata' => [
                    'custom_field' => 'custom_value',
                    'notes' => 'Updated notes',
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful();
    }
}
