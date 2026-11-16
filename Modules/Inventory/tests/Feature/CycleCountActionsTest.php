<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Acciones del conteo ciclico (2026-11-16): iniciar, registrar lo contado con ajuste
 * opcional en Movimientos y cancelar. Antes el estatus se cambiaba por PATCH y la
 * diferencia nunca se calculaba ni se ajustaba.
 */
class CycleCountActionsTest extends TestCase
{
    private Product $product;
    private Warehouse $warehouse;
    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->stock = Stock::factory()->create([
            'product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id,
            'warehouse_location_id' => null, 'quantity' => 10, 'unit_cost' => 20, 'status' => 'active',
        ]);
    }

    private function newCount(string $status = 'scheduled'): CycleCount
    {
        return CycleCount::factory()->create([
            'product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id,
            'warehouse_location_id' => null, 'status' => $status,
            'system_quantity' => null, 'counted_quantity' => null, 'variance_quantity' => null,
        ]);
    }

    private function act(CycleCount $count, string $action, array $body = [])
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/cycle-counts/{$count->id}/{$action}", $body);
    }

    public function test_iniciar_solo_desde_programado(): void
    {
        $count = $this->newCount();
        $this->act($count, 'start')->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->act($count, 'start')->assertStatus(422)->assertJsonPath('errors.0.detail', fn ($d) => str_contains($d, 'Solo se inicia un conteo programado'));
    }

    public function test_registrar_con_ajuste_deja_la_existencia_igual_a_lo_contado(): void
    {
        $count = $this->newCount('in_progress');

        $res = $this->act($count, 'record', ['counted_quantity' => 7, 'adjust' => true, 'notes' => 'Faltan 3'])->assertOk();

        $count->refresh();
        $this->assertSame('completed', $count->status);
        $this->assertEquals(10, $count->system_quantity);
        $this->assertEquals(7, $count->counted_quantity);
        $this->assertEquals(-3, $count->variance_quantity);
        $this->assertEquals(-60, $count->variance_value);
        $this->assertNotNull($count->adjustment_movement_id);
        $this->assertNotNull($count->completed_date);
        $this->assertEquals(7, (float) $this->stock->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $count->adjustment_movement_id, 'movement_type' => 'adjustment', 'quantity' => -3, 'reference_id' => $count->id,
        ]);
        $this->assertStringContainsString("Ajuste por conteo {$count->count_number}", \Modules\Inventory\Models\InventoryMovement::find($count->adjustment_movement_id)->description);
        $res->assertJsonPath('data.adjustmentMovementId', $count->adjustment_movement_id);
    }

    public function test_registrar_sin_ajuste_no_mueve_la_existencia(): void
    {
        $count = $this->newCount('in_progress');

        $this->act($count, 'record', ['counted_quantity' => 12])->assertOk();

        $count->refresh();
        $this->assertEquals(2, $count->variance_quantity);
        $this->assertNull($count->adjustment_movement_id);
        $this->assertEquals(10, (float) $this->stock->fresh()->quantity);
    }

    public function test_sin_diferencia_no_hay_ajuste(): void
    {
        $count = $this->newCount('in_progress');
        $this->act($count, 'record', ['counted_quantity' => 10, 'adjust' => true])->assertOk();
        $this->assertNull($count->fresh()->adjustment_movement_id);
        $this->assertFalse($count->fresh()->has_variance);
    }

    public function test_registrar_un_programado_pide_iniciarlo(): void
    {
        $count = $this->newCount();
        $this->act($count, 'record', ['counted_quantity' => 5])->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Inícialo primero'));
    }

    public function test_lo_contado_es_obligatorio_y_no_negativo(): void
    {
        $count = $this->newCount('in_progress');
        $this->act($count, 'record', [])->assertStatus(422)->assertJsonValidationErrors(['counted_quantity' => 'Captura la cantidad contada.']);
        $this->act($count, 'record', ['counted_quantity' => -1])->assertStatus(422)->assertJsonValidationErrors(['counted_quantity' => 'Lo contado no puede ser negativo.']);
    }

    public function test_cancelar_guarda_el_motivo_y_no_cancela_completados(): void
    {
        $count = $this->newCount();
        $this->act($count, 'cancel', ['reason' => 'Se cambió de almacén'])->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertStringContainsString('Cancelado: Se cambió de almacén', $count->fresh()->notes);

        $done = $this->newCount('completed');
        $this->act($done, 'cancel')->assertStatus(422);
    }

    public function test_sin_permiso_no_mueve_conteos(): void
    {
        $count = $this->newCount();
        $this->actingAs($this->getCustomerUser(), 'sanctum')->postJson("/api/v1/cycle-counts/{$count->id}/start")->assertForbidden();
    }

    public function test_sin_sesion_no_mueve_conteos(): void
    {
        $count = $this->newCount();
        $this->postJson("/api/v1/cycle-counts/{$count->id}/start")->assertUnauthorized();
    }
}
