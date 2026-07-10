<?php

namespace Modules\SatCatalogs\Tests\Feature;

use Tests\TestCase;

class SatTasaOCuotaIndexTest extends TestCase
{
    public function test_can_filter_by_impuesto_and_traslado(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/tasa-o-cuota?filter[impuesto]=IVA&filter[traslado]=1');

        $response->assertOk();

        $rows = collect($response->json('data'));
        $this->assertGreaterThanOrEqual(4, $rows->count());

        // Only IVA traslados
        $this->assertSame(['IVA'], $rows->pluck('impuesto')->unique()->values()->all());
        $this->assertNotContains(true, $rows->pluck('retencion')->all());

        // Includes the 16% rate and the Exento row (valor null)
        $this->assertTrue($rows->contains(fn ($row) => (float) $row['valor'] === 0.16));
        $this->assertTrue($rows->contains(fn ($row) => $row['tipo'] === 'Exento' && $row['valor'] === null));
    }

    public function test_can_filter_retenciones(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/tasa-o-cuota?filter[retencion]=1');

        $response->assertOk();

        $rows = collect($response->json('data'));

        $this->assertTrue($rows->contains(fn ($row) => $row['impuesto'] === 'ISR' && (float) $row['valor'] === 0.10));
        $this->assertTrue($rows->contains(fn ($row) => $row['impuesto'] === 'IVA' && abs((float) $row['valor'] - 0.106667) < 0.000001));
    }

    public function test_returns_full_catalog_without_filters(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/sat/tasa-o-cuota');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(6, count($response->json('data')));
    }

    public function test_guest_cannot_list_tasas(): void
    {
        $response = $this->getJson('/api/v1/sat/tasa-o-cuota');

        $response->assertStatus(401);
    }
}
