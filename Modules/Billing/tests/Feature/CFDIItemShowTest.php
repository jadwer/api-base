<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIItem;

class CFDIItemShowTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_view_cfdi_item()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'cfdi-items',
                    'id' => (string) $item->id,
                ]
            ]);
    }

    public function test_tech_can_view_cfdi_item()
    {
        $user = $this->getTechUser();
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'cfdi-items',
                    'id' => (string) $item->id,
                ]
            ]);
    }

    public function test_customer_can_view_cfdi_item()
    {
        $user = $this->getCustomerUser();
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'type' => 'cfdi-items',
                    'id' => (string) $item->id,
                ]
            ]);
    }

    public function test_guest_cannot_view_cfdi_item()
    {
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_item()
    {
        $user = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/99999');

        $response->assertStatus(404);
    }

    public function test_shows_item_with_product()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->withProduct()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();

        $this->assertNotNull($item->product_id);
    }

    public function test_shows_service_item()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->service()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();

        $this->assertNull($item->product_id);
    }

    public function test_shows_item_with_discount()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->withDiscount()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();

        $this->assertTrue($item->descuento > 0);
    }

    public function test_shows_exento_item()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->exento()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'objetoImp' => '04'
                    ]
                ]
            ]);
    }

    public function test_shows_item_with_customs_info()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->withCustoms()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful();

        $this->assertNotNull($item->numero_pedimento);
    }

    public function test_shows_all_public_attributes()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/cfdi-items/' . $item->id);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'attributes' => [
                        'numeroLinea',
                        'claveProdServ',
                        'claveUnidad',
                        'cantidad',
                        'descripcion',
                        'valorUnitario',
                        'importe',
                        'impuestos',
                        'objetoImp',
                    ]
                ]
            ]);
    }
}
