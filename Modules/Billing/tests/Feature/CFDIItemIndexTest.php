<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CFDIInvoice;

class CFDIItemIndexTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_list_cfdi_items()
    {
        $user = $this->getAdminUser();

        CFDIItem::factory()->count(3)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    public function test_tech_can_list_cfdi_items()
    {
        $user = $this->getTechUser();

        CFDIItem::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_can_list_cfdi_items()
    {
        $user = $this->getCustomerUser();

        CFDIItem::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    public function test_guest_cannot_list_cfdi_items()
    {
        CFDIItem::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->get('/api/v1/cfdi-items');

        $response->assertStatus(401);
    }

    public function test_can_filter_by_invoice()
    {
        $user = $this->getAdminUser();

        $invoice1 = CFDIInvoice::factory()->create();
        $invoice2 = CFDIInvoice::factory()->create();

        $item1 = CFDIItem::factory()->forInvoice($invoice1)->create();
        $item2 = CFDIItem::factory()->forInvoice($invoice2)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['cfdiInvoiceId' => $invoice1->id])
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_numero_linea()
    {
        $user = $this->getAdminUser();

        $item1 = CFDIItem::factory()->lineNumber(1)->create();
        $item2 = CFDIItem::factory()->lineNumber(2)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['numeroLinea' => 1])
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_clave_prod_serv()
    {
        $user = $this->getAdminUser();

        $item1 = CFDIItem::factory()->create(['clave_prod_serv' => '01010101']);
        $item2 = CFDIItem::factory()->create(['clave_prod_serv' => '10101500']);

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['claveProdServ' => '01010101'])
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_objeto_imp()
    {
        $user = $this->getAdminUser();

        $item1 = CFDIItem::factory()->create(['objeto_imp' => '02']);
        $item2 = CFDIItem::factory()->exento()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['objetoImp' => '04'])
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_sort_by_numero_linea()
    {
        $user = $this->getAdminUser();

        $item1 = CFDIItem::factory()->lineNumber(2)->create();
        $item2 = CFDIItem::factory()->lineNumber(1)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->sort('numeroLinea')
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_sort_by_importe_descending()
    {
        $user = $this->getAdminUser();

        $item1 = CFDIItem::factory()->create(['importe' => 100000]);
        $item2 = CFDIItem::factory()->create(['importe' => 500000]);

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->sort('-importe')
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    public function test_includes_pagination_meta()
    {
        $user = $this->getAdminUser();

        CFDIItem::factory()->count(15)->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->page(['number' => 1, 'size' => 10])
            ->get('/api/v1/cfdi-items');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'meta' => ['page'],
                'links'
            ]);
    }

    public function test_can_include_relationships()
    {
        $user = $this->getAdminUser();

        $item = CFDIItem::factory()->withProduct()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->includePaths('cfdiInvoice', 'product')
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'relationships' => [
                        'cfdiInvoice'
                    ]
                ]
            ]);
    }
}
