<?php

namespace Modules\SatCatalogs\Tests\Feature;

use Tests\TestCase;

class SatFormaPagoIndexTest extends TestCase
{
    public function test_authenticated_user_can_list_formas_pago(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/forma-pago');

        $response->assertOk();

        $rows = collect($response->json('data'));
        $this->assertGreaterThanOrEqual(11, $rows->count());

        $byClave = $rows->keyBy('clave');
        $this->assertSame('Efectivo', $byClave['01']['descripcion']);
        $this->assertSame('Transferencia electrónica de fondos', $byClave['03']['descripcion']);
        $this->assertSame('Por definir', $byClave['99']['descripcion']);
    }

    public function test_customer_can_list_formas_pago(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/sat/forma-pago');

        $response->assertOk();
    }

    public function test_guest_cannot_list_formas_pago(): void
    {
        $response = $this->getJson('/api/v1/sat/forma-pago');

        $response->assertStatus(401);
    }
}
