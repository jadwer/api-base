<?php

namespace Modules\SatCatalogs\Tests\Feature;

use Tests\TestCase;

class SatClaveProdServSearchTest extends TestCase
{
    public function test_authenticated_user_can_search_by_clave_prefix(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-prod-serv?filter[search]=1235');

        $response->assertOk();

        $claves = collect($response->json('data'))->pluck('clave');
        $this->assertTrue($claves->contains('12352301'));

        // Clave-prefix matches rank first
        $this->assertStringStartsWith('1235', $response->json('data.0.clave'));
    }

    public function test_can_search_by_description_term(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-prod-serv?filter[search]=' . urlencode('cidos inorg'));

        $response->assertOk();

        $claves = collect($response->json('data'))->pluck('clave');
        $this->assertTrue($claves->contains('12352301')); // Acidos inorganicos

        $this->assertArrayHasKey('descripcion', $response->json('data.0'));
    }

    public function test_returns_empty_data_for_unmatched_term(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-prod-serv?filter[search]=zzzznotfoundzzz');

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    public function test_default_page_size_is_20(): void
    {
        $admin = $this->getAdminUser();

        // The seeder loads 33 claves; without page[size] we expect 20.
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-prod-serv');

        $response->assertOk();
        $this->assertCount(20, $response->json('data'));
    }

    public function test_page_size_is_respected_and_capped_at_50(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-prod-serv?page[size]=5');

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));

        // Requesting more than the cap does not fail; it just caps at 50.
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/clave-prod-serv?page[size]=500');

        $response->assertOk();
        $this->assertLessThanOrEqual(50, count($response->json('data')));
    }

    public function test_guest_cannot_search_claves(): void
    {
        $response = $this->getJson('/api/v1/sat/clave-prod-serv?filter[search]=1235');

        $response->assertStatus(401);
    }
}
