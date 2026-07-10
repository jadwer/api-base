<?php

namespace Modules\SatCatalogs\Tests\Feature;

use Tests\TestCase;

class SatClaveUnidadSearchTest extends TestCase
{
    public function test_authenticated_user_can_search_by_clave(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-unidad?filter[search]=H87');

        $response->assertOk();
        $this->assertSame('H87', $response->json('data.0.clave'));
        $this->assertSame('Pieza', $response->json('data.0.nombre'));
    }

    public function test_can_search_by_nombre(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-unidad?filter[search]=Kilo');

        $response->assertOk();

        $claves = collect($response->json('data'))->pluck('clave');
        $this->assertTrue($claves->contains('KGM'));
    }

    public function test_page_size_limits_results(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-unidad?page[size]=3');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_guest_cannot_search_unidades(): void
    {
        $response = $this->getJson('/api/v1/sat/clave-unidad?filter[search]=H87');

        $response->assertStatus(401);
    }
}
