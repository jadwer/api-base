<?php

namespace Modules\Inventory\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryMovement;
use Tests\TestCase;

/** Correccion del corrimiento de zona horaria de los movimientos capturados en pantalla (2026-11-16). */
class FixMovementTimezoneCommandTest extends TestCase
{
    private function movement(string $date, string $createdAt, string $referenceType = 'manual'): InventoryMovement
    {
        $m = InventoryMovement::factory()->create(['reference_type' => $referenceType, 'status' => 'completed']);
        DB::table('inventory_movements')->where('id', $m->id)->update(['movement_date' => $date, 'created_at' => $createdAt, 'metadata' => null]);

        return $m;
    }

    private function dateOf(InventoryMovement $m): string
    {
        return (string) DB::table('inventory_movements')->where('id', $m->id)->value('movement_date');
    }

    public function test_corrige_solo_los_capturados_en_pantalla_y_una_sola_vez(): void
    {
        // Capturado a las 21:43 de Mexico: se guardo 21:43 y se creo a las 03:43 UTC del dia siguiente.
        $form = $this->movement('2026-11-15 21:43:00', '2026-11-16 03:43:18');
        // Del sistema (now()): fecha igual a la creacion.
        $system = $this->movement('2026-11-10 18:02:00', '2026-11-10 18:02:30', 'purchase');
        // Recepcion con segundos: no viene del input.
        $withSeconds = $this->movement('2026-11-09 10:15:27', '2026-11-09 16:15:27');
        // Origen del sistema que no ofrece el alta.
        $remission = $this->movement('2026-11-08 09:00:00', '2026-11-08 20:00:00', 'remission');
        // Creado despues del corte (alta ya corregida).
        $after = $this->movement('2026-11-17 04:00:00', '2026-11-17 04:10:00');

        $this->artisan('inventory:fix-movement-timezone')->assertSuccessful();
        $this->assertSame('2026-11-15 21:43:00', $this->dateOf($form), 'la simulacion no cambia nada');

        $this->artisan('inventory:fix-movement-timezone', ['--apply' => true, '--until' => '2026-11-16 12:00'])->assertSuccessful();

        $this->assertSame('2026-11-16 03:43:00', $this->dateOf($form));
        $this->assertSame('2026-11-10 18:02:00', $this->dateOf($system));
        $this->assertSame('2026-11-09 10:15:27', $this->dateOf($withSeconds));
        $this->assertSame('2026-11-08 09:00:00', $this->dateOf($remission));
        $this->assertSame('2026-11-17 04:00:00', $this->dateOf($after));
        $meta = json_decode((string) DB::table('inventory_movements')->where('id', $form->id)->value('metadata'), true);
        $this->assertSame('2026-11-15 21:43:00', $meta['timezone_fix']['from']);

        // Segunda corrida: la marca impide moverlo otras seis horas.
        $this->artisan('inventory:fix-movement-timezone', ['--apply' => true, '--until' => '2026-11-16 12:00'])->assertSuccessful();
        $this->assertSame('2026-11-16 03:43:00', $this->dateOf($form));
    }

    public function test_aplicar_sin_corte_se_rechaza(): void
    {
        $this->artisan('inventory:fix-movement-timezone', ['--apply' => true])->assertFailed();
    }
}
