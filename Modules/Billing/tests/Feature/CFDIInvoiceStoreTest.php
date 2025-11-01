<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Contacts\Models\Contact;

class CFDIInvoiceStoreTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_create_cfdi_invoice()
    {
        $user = $this->getAdminUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'tipoComprobante' => 'I',
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test SA de CV',
                'receptorUsoCfdi' => 'G03',
                'subtotal' => 100000,
                'total' => 116000,
                'iva' => 16000,
                'moneda' => 'MXN',
                'tipoCambio' => 1.000000,
                'metodoPago' => 'PUE',
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => 'cfdi-invoices',
                    'attributes' => [
                        'series' => 'F',
                        'folio' => 1001,
                    ]
                ]
            ]);

        $this->assertDatabaseHas('cfdi_invoices', [
            'series' => 'F',
            'folio' => 1001,
        ]);
    }

    public function test_tech_cannot_create_cfdi_invoice()
    {
        $user = $this->getTechUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_cfdi_invoice()
    {
        $user = $this->getCustomerUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_cfdi_invoice()
    {
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(401);
    }

    public function test_company_setting_is_required()
    {
        $user = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(422);
    }

    public function test_contact_is_required()
    {
        $user = $this->getAdminUser();
        $companySetting = CompanySetting::factory()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(422);
    }

    public function test_receptor_rfc_is_required()
    {
        $user = $this->getAdminUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(422);
    }

    public function test_receptor_rfc_must_be_13_characters()
    {
        $user = $this->getAdminUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorRfc' => 'SHORT',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(422);
    }

    public function test_subtotal_is_required()
    {
        $user = $this->getAdminUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'total' => 116000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(422);
    }

    public function test_total_is_required()
    {
        $user = $this->getAdminUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1001,
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertStatus(422);
    }

    public function test_can_create_egreso_invoice()
    {
        $user = $this->getAdminUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'N',
                'folio' => 101,
                'tipoComprobante' => 'E',
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'cfdiRelacionadoTipo' => '01',
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertCreated();
    }

    public function test_can_create_ppd_invoice()
    {
        $user = $this->getAdminUser();
        $companySetting = CompanySetting::factory()->create();
        $contact = Contact::factory()->customer()->create();

        $data = [
            'type' => 'cfdi-invoices',
            'attributes' => [
                'companySettingId' => $companySetting->id,
                'contactId' => $contact->id,
                'series' => 'F',
                'folio' => 1002,
                'receptorRfc' => 'XAXX010101000',
                'receptorNombre' => 'Cliente Test',
                'subtotal' => 100000,
                'total' => 116000,
                'metodoPago' => 'PPD',
                'condicionesPago' => 'Pago en 30 días',
                'fechaEmision' => now()->toISOString(),
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-invoices');

        $response->assertCreated();
    }
}
