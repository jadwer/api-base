<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\InventoryMovement;
use Tests\TestCase;

/** La consulta del movimiento (rediseno 2026-11-15) muestra quien reviso la calidad. */
class InventoryMovementQualityCheckerIncludeTest extends TestCase
{
    public function test_la_consulta_trae_a_quien_reviso_la_calidad(): void
    {
        $admin = $this->getAdminUser();
        $movement = InventoryMovement::factory()->create([
            'quality_checked' => true,
            'quality_checked_at' => now(),
            'quality_checked_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('inventory-movements')
            ->includePaths('qualityChecker')
            ->get("/api/v1/inventory-movements/{$movement->id}");

        $response->assertOk()
            ->assertJsonPath('data.relationships.qualityChecker.data.id', (string) $admin->id);
        $this->assertSame($admin->name, collect($response->json('included'))->firstWhere('type', 'users')['attributes']['name'] ?? null);
    }
}
