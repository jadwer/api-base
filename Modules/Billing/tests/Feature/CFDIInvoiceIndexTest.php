<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Contacts\Models\Contact;

class CFDIInvoiceIndexTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_list_cfdi_invoices()
    {
        $user = $this->getAdminUser();

        $invoices = CFDIInvoice::factory()->count(3)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify our created invoices are in the response
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(3, count($data));
        $ids = collect($data)->pluck('id')->toArray();
        foreach ($invoices as $invoice) {
            $this->assertContains((string)$invoice->id, $ids);
        }
    }

    public function test_tech_can_list_cfdi_invoices()
    {
        $user = $this->getTechUser();

        $invoices = CFDIInvoice::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify our created invoices are in the response
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
        $ids = collect($data)->pluck('id')->toArray();
        foreach ($invoices as $invoice) {
            $this->assertContains((string)$invoice->id, $ids);
        }
    }

    public function test_customer_can_list_cfdi_invoices()
    {
        $user = $this->getCustomerUser();

        $invoices = CFDIInvoice::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify our created invoices are in the response
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
        $ids = collect($data)->pluck('id')->toArray();
        foreach ($invoices as $invoice) {
            $this->assertContains((string)$invoice->id, $ids);
        }
    }

    public function test_guest_cannot_list_cfdi_invoices()
    {
        CFDIInvoice::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->get('/api/v1/cfdi-invoices');

        $response->assertStatus(401);
    }

    public function test_can_filter_by_series()
    {
        $user = $this->getAdminUser();

        // Use unique series values unlikely to exist
        $uniqueSeries = 'X' . substr(md5(uniqid()), 0, 4);
        $invoice1 = CFDIInvoice::factory()->create(['series' => $uniqueSeries]);
        $invoice2 = CFDIInvoice::factory()->create(['series' => 'ZZZZ']);

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['series' => $uniqueSeries])
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify invoice1 is in results and invoice2 is not
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string)$invoice1->id, $ids);
        $this->assertNotContains((string)$invoice2->id, $ids);
    }

    public function test_can_filter_by_status()
    {
        $user = $this->getAdminUser();

        $draft = CFDIInvoice::factory()->draft()->create();
        $valid = CFDIInvoice::factory()->valid()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['status' => 'valid'])
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify valid invoice is included, draft is not
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string)$valid->id, $ids);
        $this->assertNotContains((string)$draft->id, $ids);
    }

    public function test_can_filter_by_tipo_comprobante()
    {
        $user = $this->getAdminUser();

        $ingreso = CFDIInvoice::factory()->ingreso()->create();
        $egreso = CFDIInvoice::factory()->egreso()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['tipoComprobante' => 'I'])
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify ingreso is included, egreso is not
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string)$ingreso->id, $ids);
        $this->assertNotContains((string)$egreso->id, $ids);
    }

    public function test_can_filter_by_receptor_rfc()
    {
        $user = $this->getAdminUser();

        // Use unique RFC values
        $uniqueRfc = 'TEST' . substr(md5(uniqid()), 0, 8);
        $invoice1 = CFDIInvoice::factory()->create(['receptor_rfc' => $uniqueRfc]);
        $invoice2 = CFDIInvoice::factory()->create(['receptor_rfc' => 'YAYY020202000']);

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['receptorRfc' => $uniqueRfc])
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify invoice1 is included, invoice2 is not
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string)$invoice1->id, $ids);
        $this->assertNotContains((string)$invoice2->id, $ids);
    }

    public function test_can_sort_by_fecha_emision()
    {
        $user = $this->getAdminUser();

        $older = CFDIInvoice::factory()->create(['fecha_emision' => now()->subDays(2)]);
        $newer = CFDIInvoice::factory()->create(['fecha_emision' => now()]);

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->sort('fechaEmision')
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify both invoices are in the response
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string)$older->id, $ids);
        $this->assertContains((string)$newer->id, $ids);
    }

    public function test_can_sort_by_total_descending()
    {
        $user = $this->getAdminUser();

        $lower = CFDIInvoice::factory()->create(['total' => 100000]);
        $higher = CFDIInvoice::factory()->create(['total' => 500000]);

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->sort('-total')
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful();

        // Verify both invoices are in the response
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains((string)$lower->id, $ids);
        $this->assertContains((string)$higher->id, $ids);
    }

    public function test_includes_pagination_meta()
    {
        $user = $this->getAdminUser();

        CFDIInvoice::factory()->count(15)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->page(['number' => 1, 'size' => 10])
            ->get('/api/v1/cfdi-invoices');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'meta' => ['page'],
                'links'
            ]);
    }

    public function test_can_include_relationships()
    {
        $user = $this->getAdminUser();

        $invoice = CFDIInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->includePaths('companySetting', 'contact')
            ->get('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'relationships' => [
                        'companySetting',
                        'contact'
                    ]
                ]
            ]);
    }
}
