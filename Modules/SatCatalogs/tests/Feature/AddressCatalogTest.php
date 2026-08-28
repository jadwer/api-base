<?php

namespace Modules\SatCatalogs\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SatCatalogs\Models\SatCodigoPostal;
use Modules\SatCatalogs\Models\SatColonia;
use Tests\TestCase;

class AddressCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function seedAddressCatalog(): void
    {
        SatCodigoPostal::create([
            'codigo_postal' => '06600',
            'estado_clave' => 'CMX',
            'estado' => 'Ciudad de México',
            'municipio_clave' => '015',
            'municipio' => 'Cuauhtémoc',
            'localidad_clave' => '06',
        ]);
        SatCodigoPostal::create([
            'codigo_postal' => '64000',
            'estado_clave' => 'NLE',
            'estado' => 'Nuevo León',
            'municipio_clave' => '039',
            'municipio' => 'Monterrey',
        ]);
        SatColonia::create(['codigo_postal' => '06600', 'clave' => '0930', 'nombre' => 'Juárez']);
        SatColonia::create(['codigo_postal' => '64000', 'clave' => '0001', 'nombre' => 'Centro']);
        SatColonia::create(['codigo_postal' => '64000', 'clave' => '0002', 'nombre' => 'Barrio Antiguo']);
    }

    /** @test */
    public function postal_code_lookup_returns_state_municipality_and_colonias(): void
    {
        $this->seedAddressCatalog();
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/address/postal-codes/64000');

        $response->assertSuccessful()
            ->assertJsonPath('data.estado', 'Nuevo León')
            ->assertJsonPath('data.municipio', 'Monterrey')
            ->assertJsonPath('data.estadoClave', 'NLE');

        $colonias = collect($response->json('data.colonias'))->pluck('nombre')->all();
        // Ordenadas por nombre
        $this->assertSame(['Barrio Antiguo', 'Centro'], $colonias);
    }

    /** @test */
    public function unknown_postal_code_returns_404_not_a_hard_error(): void
    {
        $this->seedAddressCatalog();
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/address/postal-codes/00000')
            ->assertStatus(404);
    }

    /** @test */
    public function malformed_postal_code_returns_422(): void
    {
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/address/postal-codes/ABC12')
            ->assertStatus(422);
    }

    /** @test */
    public function estados_endpoint_lists_distinct_states(): void
    {
        $this->seedAddressCatalog();
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/address/estados');

        $response->assertSuccessful();
        $nombres = collect($response->json('data'))->pluck('nombre')->all();
        $this->assertSame(['Ciudad de México', 'Nuevo León'], $nombres);
    }

    /** @test */
    public function municipios_endpoint_filters_by_state(): void
    {
        $this->seedAddressCatalog();
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/address/estados/NLE/municipios');

        $response->assertSuccessful();
        $this->assertSame([['clave' => '039', 'nombre' => 'Monterrey']], $response->json('data'));
    }

    /** @test */
    public function guest_cannot_use_address_catalogs(): void
    {
        $this->getJson('/api/v1/sat/address/postal-codes/06600')->assertStatus(401);
        $this->getJson('/api/v1/sat/address/estados')->assertStatus(401);
    }

    /** @test */
    public function customer_can_use_address_catalogs(): void
    {
        $this->seedAddressCatalog();
        $customer = $this->getCustomerUser();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/sat/address/postal-codes/06600')
            ->assertSuccessful()
            ->assertJsonPath('data.municipio', 'Cuauhtémoc');
    }
}
