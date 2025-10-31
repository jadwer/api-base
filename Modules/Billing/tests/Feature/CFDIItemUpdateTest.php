<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIItem;

class CFDIItemUpdateTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_update_cfdi_item()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'descripcion' => 'Updated description',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'descripcion' => 'Updated description'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('cfdi_items', [
            'id' => $item->id,
            'descripcion' => 'Updated description',
        ]);
    }

    public function test_tech_cannot_update_cfdi_item()
    {
        $user = $this->getTechUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'descripcion' => 'Updated',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_update_cfdi_item()
    {
        $user = $this->getCustomerUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'descripcion' => 'Updated',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_cfdi_item()
    {
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'descripcion' => 'Updated',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertStatus(401);
    }

    public function test_can_update_cantidad()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create(['cantidad' => 1.0]);

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'cantidad' => 5.0,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();
    }

    public function test_can_update_valor_unitario()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'valorUnitario' => 200000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();
    }

    public function test_can_update_importe()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'importe' => 500000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();
    }

    public function test_can_add_discount()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create(['descuento' => 0]);

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'descuento' => 10000,
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();
    }

    public function test_can_update_clave_prod_serv()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'claveProdServ' => '99999999',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();
    }

    public function test_can_update_clave_unidad()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'claveUnidad' => 'KGM',
                'unidad' => 'Kilogramo',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();
    }

    public function test_can_update_impuestos()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'impuestos' => [
                    'traslados' => [
                        [
                            'base' => 100000,
                            'impuesto' => '002',
                            'tipo_factor' => 'Tasa',
                            'tasa_o_cuota' => '0.080000',
                            'importe' => 8000,
                        ]
                    ]
                ],
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();
    }

    public function test_can_update_metadata()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $data = [
            'type' => 'cfdi-items',
            'id' => (string) $item->id,
            'attributes' => [
                'metadata' => [
                    'custom_field' => 'custom_value',
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->withData($data)
            ->patch('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();
    }
}
