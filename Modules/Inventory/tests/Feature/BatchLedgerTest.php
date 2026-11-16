<?php

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Cuenta de lotes en los movimientos (decision de Gabino, 2026-11-16): las cantidades del lote
 * salen de las entradas, salidas, ajustes y transferencias; ya no se capturan a mano.
 */
class BatchLedgerTest extends TestCase
{
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
    }

    private function newBatch(float $quantity = 10, array $extra = []): string
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('product-batches')
            ->withData([
                'type' => 'product-batches',
                'attributes' => array_merge([
                    'batchNumber' => 'L-'.uniqid(), 'lotNumber' => 'FAB-77', 'expirationDate' => '2027-06-30',
                    'initialQuantity' => $quantity, 'unitCost' => 12.5, 'status' => 'active',
                ], $extra),
                'relationships' => [
                    'product' => ['data' => ['type' => 'products', 'id' => (string) $this->product->id]],
                    'warehouse' => ['data' => ['type' => 'warehouses', 'id' => (string) $this->warehouse->id]],
                ],
            ])->post('/api/v1/product-batches');
        $response->assertCreated();

        return $response->json('data.id');
    }

    private function move(string $type, float $quantity, ?string $batchId, array $extra = [])
    {
        $admin = $this->getAdminUser();

        return $this->actingAs($admin, 'sanctum')->jsonApi()->expects('inventory-movements')
            ->withData(['type' => 'inventory-movements', 'attributes' => array_merge([
                'movementType' => $type, 'referenceType' => 'manual', 'movementDate' => '2026-11-16T10:00:00Z',
                'quantity' => $quantity, 'unitCost' => 12.5, 'productId' => $this->product->id,
                'warehouseId' => $this->warehouse->id, 'userId' => $admin->id, 'productBatchId' => $batchId,
                'qualityChecked' => true, 'qualityCheckedBy' => $admin->id,
            ], $extra)])->post('/api/v1/inventory-movements');
    }

    private function batch(string $id): ProductBatch
    {
        return ProductBatch::findOrFail($id);
    }

    public function test_el_alta_del_lote_registra_su_entrada_y_suma_al_stock(): void
    {
        $id = $this->newBatch(10);

        $batch = $this->batch($id);
        $this->assertEquals(10, $batch->current_quantity);
        $this->assertEquals(10, $batch->initial_quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_batch_id' => $id, 'movement_type' => 'entry', 'quantity' => 10]);
        $this->assertEquals(10, (float) Stock::where('product_id', $this->product->id)->where('warehouse_id', $this->warehouse->id)->value('quantity'));
    }

    public function test_la_salida_resta_del_lote_y_lo_agota(): void
    {
        $id = $this->newBatch(5);

        $this->move('exit', 3, $id)->assertCreated();
        $this->assertEquals(2, $this->batch($id)->current_quantity);

        $this->move('exit', 2, $id)->assertCreated();
        $batch = $this->batch($id);
        $this->assertEquals(0, $batch->current_quantity);
        $this->assertSame('consumed', $batch->status);
    }

    public function test_no_deja_sacar_mas_de_lo_que_tiene_el_lote_y_lo_dice_en_espanol(): void
    {
        $id = $this->newBatch(2);
        // Stock de sobra en el almacen para que la regla que corte sea la del lote
        Stock::where('product_id', $this->product->id)->update(['quantity' => 50]);

        $this->move('exit', 3, $id)
            ->assertStatus(422)
            ->assertJsonFragment(['detail' => "El lote {$this->batch($id)->batch_number} tiene 2 disponible; se pidieron 3."]);
        $this->assertEquals(2, $this->batch($id)->current_quantity);
        $this->assertEquals(50, (float) Stock::where('product_id', $this->product->id)->value('quantity'), 'la transaccion se revierte completa');
    }

    public function test_no_deja_sacar_de_un_lote_en_cuarentena_pero_si_ajustarlo(): void
    {
        $id = $this->newBatch(4);
        ProductBatch::whereKey($id)->update(['status' => 'quarantine']);

        $this->move('exit', 1, $id)->assertStatus(422)->assertJsonFragment(['detail' => "El lote {$this->batch($id)->batch_number} está en cuarentena; no se puede sacar producto de él."]);

        $this->move('adjustment', -1, $id)->assertCreated();
        $this->assertEquals(3, $this->batch($id)->current_quantity);
        $this->move('adjustment', 2, $id)->assertCreated();
        $this->assertEquals(5, $this->batch($id)->current_quantity);
        $this->assertEquals(5, $this->batch($id)->initial_quantity, 'la inicial sigue a lo que ha entrado');
    }

    public function test_el_lote_debe_ser_del_mismo_producto_y_almacen(): void
    {
        $id = $this->newBatch(4);
        $other = Product::factory()->create();
        Stock::factory()->create(['product_id' => $other->id, 'warehouse_id' => $this->warehouse->id, 'warehouse_location_id' => null, 'quantity' => 9]);

        $this->move('exit', 1, $id, ['productId' => $other->id])
            ->assertStatus(422)
            ->assertJsonFragment(['detail' => "El lote {$this->batch($id)->batch_number} es de otro producto."]);
    }

    public function test_la_transferencia_mueve_la_cantidad_al_lote_espejo_del_destino(): void
    {
        $id = $this->newBatch(6);
        $destination = Warehouse::factory()->create();

        $response = $this->move('transfer', 4, $id, ['destinationWarehouseId' => $destination->id]);
        $response->assertCreated();

        $this->assertEquals(2, $this->batch($id)->current_quantity);
        $mirror = ProductBatch::where('warehouse_id', $destination->id)->firstOrFail();
        $this->assertEquals(4, $mirror->current_quantity);
        $this->assertSame('FAB-77', $mirror->lot_number);
        $this->assertSame((int) $id, (int) $mirror->metadata['source_batch_id']);
        $this->assertSame((string) $mirror->id, (string) $response->json('data.attributes.destinationBatchId'));

        // Una segunda transferencia usa el mismo espejo
        $this->move('transfer', 1, $id, ['destinationWarehouseId' => $destination->id])->assertCreated();
        $this->assertSame(1, ProductBatch::where('warehouse_id', $destination->id)->count());
        $this->assertEquals(5, $mirror->fresh()->current_quantity);
    }

    public function test_la_cantidad_del_lote_ya_no_se_edita_a_mano(): void
    {
        $id = $this->newBatch(3);

        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('product-batches')
            ->withData(['type' => 'product-batches', 'id' => $id, 'attributes' => ['currentQuantity' => 99]])
            ->patch("/api/v1/product-batches/{$id}")
            ->assertStatus(422)
            ->assertJsonFragment(['detail' => 'La cantidad del lote no se captura a mano: registra una entrada o un ajuste del lote en Movimientos.']);

        // Lo demas del lote si se edita
        $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('product-batches')
            ->withData(['type' => 'product-batches', 'id' => $id, 'attributes' => ['qualityNotes' => 'Revisado', 'supplierName' => 'Proveedor X']])
            ->patch("/api/v1/product-batches/{$id}")
            ->assertOk();
        $this->assertEquals(3, $this->batch($id)->current_quantity);
    }

    public function test_un_ajuste_a_negativo_responde_422_con_el_motivo_y_no_500(): void
    {
        $this->newBatch(1);

        $this->move('adjustment', -5, null)
            ->assertStatus(422)
            ->assertJsonFragment(['detail' => 'El ajuste dejaría la existencia en negativo. Actual: 1.0000, ajuste: -5.']);
    }

    public function test_los_movimientos_del_lote_se_filtran_para_su_trazabilidad(): void
    {
        $id = $this->newBatch(5);
        $destination = Warehouse::factory()->create();
        $this->move('exit', 1, $id)->assertCreated();
        $this->move('transfer', 2, $id, ['destinationWarehouseId' => $destination->id])->assertCreated();
        $mirror = ProductBatch::where('warehouse_id', $destination->id)->firstOrFail();

        $own = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('inventory-movements')
            ->get("/api/v1/inventory-movements?filter[batch]={$id}")->assertOk()->json('data');
        $this->assertEqualsCanonicalizing(['entry', 'exit', 'transfer'], array_column(array_column($own, 'attributes'), 'movementType'));

        $mirrorMoves = $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->expects('inventory-movements')
            ->get("/api/v1/inventory-movements?filter[batch]={$mirror->id}")->assertOk()->json('data');
        $this->assertSame(['transfer'], array_column(array_column($mirrorMoves, 'attributes'), 'movementType'));
    }
}
