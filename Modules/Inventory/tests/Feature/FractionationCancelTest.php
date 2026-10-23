<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\ProductConversion;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\FolioSequence;
use Tests\TestCase;

/**
 * Cancelacion de fraccionamiento (G10 de granel, 23-10): la reversa regresa
 * el origen y retira lo producido, una sola vez, y se niega si lo producido
 * ya se vendio o movio.
 */
class FractionationCancelTest extends TestCase
{
    private Product $source;
    private Product $dest;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        FolioSequence::firstOrCreate(['document_type' => 'fractionation'], [
            'prefix' => 'FRAC', 'include_year' => true, 'year_format' => 'y', 'separator' => '-',
            'padding' => 6, 'current_sequence' => 0, 'reset_yearly' => false, 'is_active' => true,
        ]);

        $this->source = Product::factory()->create(['name' => 'Granel 15L']);
        $this->dest = Product::factory()->create(['name' => 'Botella 1L']);
        $this->warehouse = Warehouse::factory()->create();

        ProductConversion::factory()->create([
            'source_product_id' => $this->source->id,
            'destination_product_id' => $this->dest->id,
            'conversion_factor' => 10.0,
            'waste_percentage' => 0,
            'is_active' => true,
        ]);

        Stock::factory()->create([
            'product_id' => $this->source->id, 'warehouse_id' => $this->warehouse->id,
            'warehouse_location_id' => null, 'quantity' => 100, 'reserved_quantity' => 0, 'unit_cost' => 50.0,
        ]);
    }

    private function fractionate(): Fractionation
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->postJson('/api/v1/fraccionamiento/execute', [
            'source_product_id' => $this->source->id,
            'destination_product_id' => $this->dest->id,
            'source_quantity' => 5,
            'warehouse_id' => $this->warehouse->id,
        ]);
        $response->assertCreated();

        return Fractionation::findOrFail($response->json('data.id'));
    }

    private function qty(Product $product): float
    {
        return (float) Stock::where('product_id', $product->id)->where('warehouse_id', $this->warehouse->id)->sum('quantity');
    }

    public function test_cancel_reverses_stock_once(): void
    {
        $fractionation = $this->fractionate();
        $this->assertEqualsWithDelta(95, $this->qty($this->source), 0.001);
        $this->assertEqualsWithDelta(50, $this->qty($this->dest), 0.001);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/fraccionamiento/{$fractionation->id}/cancel", ['reason' => 'Captura equivocada'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellationReason', 'Captura equivocada');

        $this->assertEqualsWithDelta(100, $this->qty($this->source), 0.001);
        $this->assertEqualsWithDelta(0, $this->qty($this->dest), 0.001);
        $this->assertSame(2, InventoryMovement::where('idempotency_key', 'like', "fractionation_cancel:{$fractionation->id}:%")->count());

        // Una segunda cancelacion no vuelve a mover inventario
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/fraccionamiento/{$fractionation->id}/cancel")
            ->assertStatus(422);
        $this->assertEqualsWithDelta(100, $this->qty($this->source), 0.001);
    }

    public function test_cannot_cancel_when_production_was_already_consumed(): void
    {
        $fractionation = $this->fractionate();
        Stock::where('product_id', $this->dest->id)->update(['quantity' => 10]);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/fraccionamiento/{$fractionation->id}/cancel");

        $response->assertStatus(422);
        $this->assertStringContainsString('Ya se vendió o movió parte de lo fraccionado', (string) $response->json('message'));
        $this->assertSame('completed', $fractionation->fresh()->status);
        $this->assertEqualsWithDelta(95, $this->qty($this->source), 0.001);
    }

    public function test_guest_cannot_cancel(): void
    {
        $fractionation = $this->fractionate();
        auth()->forgetGuards();

        $this->postJson("/api/v1/fraccionamiento/{$fractionation->id}/cancel")->assertUnauthorized();
    }
}
