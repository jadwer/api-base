<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIItem;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Product\Models\Product;

class CFDIItemStoreTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_create_cfdi_item()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'claveUnidad' => 'E48',
                'unidad' => 'Servicio',
                'cantidad' => 1.0,
                'descripcion' => 'Servicios profesionales',
                'valorUnitario' => 100000,
                'importe' => 100000,
                'objetoImp' => '02',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'type' => 'cfdi-items',
                    'attributes' => [
                        'numeroLinea' => 1,
                    ]
                ]
            ]);

        $this->assertDatabaseHas('cfdi_items', [
            'cfdi_invoice_id' => $invoice->id,
            'numero_linea' => 1,
        ]);
    }

    public function test_tech_cannot_create_cfdi_item()
    {
        $user = $this->getTechUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'descripcion' => 'Test',
                'valorUnitario' => 100000,
                'importe' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_cfdi_item()
    {
        $user = $this->getCustomerUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'descripcion' => 'Test',
                'valorUnitario' => 100000,
                'importe' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_cfdi_item()
    {
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'descripcion' => 'Test',
                'valorUnitario' => 100000,
                'importe' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(401);
    }

    public function test_invoice_is_required()
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'descripcion' => 'Test',
                'valorUnitario' => 100000,
                'importe' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(422);
    }

    public function test_clave_prod_serv_is_required()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'cantidad' => 1.0,
                'descripcion' => 'Test',
                'valorUnitario' => 100000,
                'importe' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(422);
    }

    public function test_cantidad_is_required()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'descripcion' => 'Test',
                'valorUnitario' => 100000,
                'importe' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(422);
    }

    public function test_descripcion_is_required()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'valorUnitario' => 100000,
                'importe' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(422);
    }

    public function test_valor_unitario_is_required()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'descripcion' => 'Test',
                'importe' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(422);
    }

    public function test_importe_is_required()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'descripcion' => 'Test',
                'valorUnitario' => 100000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertStatus(422);
    }

    public function test_can_create_with_product()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();
        $product = Product::first();

        if (!$product) {
            $this->markTestSkipped('No products available');
        }

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'productId' => $product->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 2.0,
                'descripcion' => 'Product description',
                'valorUnitario' => 100000,
                'importe' => 200000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertCreated();
    }

    public function test_can_create_with_discount()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'descripcion' => 'Test with discount',
                'valorUnitario' => 100000,
                'importe' => 100000,
                'descuento' => 10000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertCreated();
    }

    public function test_can_create_with_taxes()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'attributes' => [
                'cfdiInvoiceId' => $invoice->id,
                'numeroLinea' => 1,
                'claveProdServ' => '01010101',
                'cantidad' => 1.0,
                'descripcion' => 'Test with taxes',
                'valorUnitario' => 100000,
                'importe' => 100000,
                'impuestos' => [
                    'traslados' => [
                        [
                            'base' => 100000,
                            'impuesto' => '002',
                            'tipo_factor' => 'Tasa',
                            'tasa_o_cuota' => '0.160000',
                            'importe' => 16000,
                        ]
                    ]
                ],
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->post('/api/v1/cfdi-items');

        $response->assertCreated();
    }
}
