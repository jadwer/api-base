<?php

namespace Modules\Inventory\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Models\ProductConversion;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Modules\Inventory\Services\FractionationService;
use Modules\Inventory\Services\InventoryMovementService;
use Modules\Product\Models\Product;
use Modules\Sales\Models\FolioSequence;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * P3 granel (2026-10-10): fraccionamiento seguro.
 *
 * Idempotencia (incluida la carrera), disponible vs reservado, costo destino
 * con merma absorbida, GL sin COGS, lotes origen -> destino, ubicacion
 * obligatoria con varias filas y piezas enteras.
 */
class FractionationSafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;
    private int $batchSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['fractionations.store', 'fractionations.index'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }
        Role::firstOrCreate(['name' => 'admin'])->givePermissionTo(['fractionations.store', 'fractionations.index']);
        Role::firstOrCreate(['name' => 'viewer'])->givePermissionTo(['fractionations.index']);

        FolioSequence::firstOrCreate(
            ['document_type' => 'fractionation'],
            [
                'prefix' => 'FRAC',
                'include_year' => true,
                'year_format' => 'y',
                'separator' => '-',
                'padding' => 6,
                'current_sequence' => 0,
                'reset_yearly' => false,
                'is_active' => true,
            ]
        );

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->warehouse = Warehouse::factory()->create();
    }

    // ------------------------------------------------------------------
    // Ayudas
    // ------------------------------------------------------------------

    /**
     * @return array{0: Product, 1: Product, 2: ProductConversion}
     */
    private function bulkPair(float $factor = 40, float $waste = 0, bool $wholeUnits = false): array
    {
        $source = Product::factory()->create(['name' => 'Alcohol isopropilico granel', 'cost' => null]);
        $dest = Product::factory()->create(['name' => 'Alcohol 25 ml', 'cost' => null]);
        $conversion = ProductConversion::factory()->create([
            'source_product_id' => $source->id,
            'destination_product_id' => $dest->id,
            'conversion_factor' => $factor,
            'waste_percentage' => $waste,
            'whole_units' => $wholeUnits,
            'is_active' => true,
        ]);

        return [$source, $dest, $conversion];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function stock(Product $product, float $quantity, float $unitCost, array $overrides = []): Stock
    {
        return Stock::factory()->create(array_merge([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'warehouse_location_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'unit_cost' => $unitCost,
        ], $overrides));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function batch(Product $product, float $current, array $overrides = []): ProductBatch
    {
        $n = ++$this->batchSeq;

        return ProductBatch::factory()->create(array_merge([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'warehouse_location_id' => null,
            'batch_number' => "LOTE-ORIGEN-{$n}",
            'lot_number' => "L{$n}-2026",
            'manufacturing_date' => '2026-01-15',
            'expiration_date' => now()->addYear()->format('Y-m-d'),
            'best_before_date' => null,
            'initial_quantity' => $current,
            'current_quantity' => $current,
            'reserved_quantity' => 0,
            'unit_cost' => 30,
            'status' => 'active',
            'quality_status' => 'passed',
            'supplier_name' => 'Proveedor UE',
            'supplier_batch' => 'SUP-1',
            'metadata' => null,
        ], $overrides));
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function payload(Product $source, Product $dest, float $qty, array $extra = []): array
    {
        return array_merge([
            'source_product_id' => $source->id,
            'destination_product_id' => $dest->id,
            'source_quantity' => $qty,
            'warehouse_id' => $this->warehouse->id,
        ], $extra);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function execute(array $payload): TestResponse
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/fraccionamiento/execute', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function calculate(array $payload, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->admin, 'sanctum')->postJson('/api/v1/fraccionamiento/calculate', $payload);
    }

    private function createGLInfrastructure(): void
    {
        Journal::firstOrCreate(['code' => 'GL'], [
            'name' => 'General Ledger', 'description' => 'GL', 'prefix' => 'GL',
            'type' => 'general', 'status' => 'active', 'metadata' => [],
        ]);
        $now = now();
        FiscalPeriod::firstOrCreate(['year' => $now->year, 'month' => $now->month], [
            'name' => $now->format('Y-m'),
            'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
            'status' => 'open', 'metadata' => [],
        ]);
        $accounts = [
            config('inventory.gl_accounts.inventory_asset', '115-001') => ['asset', 'debit'],
            config('inventory.gl_accounts.inventory_accrual', '205-001') => ['liability', 'credit'],
            config('inventory.gl_accounts.cogs', '601-001') => ['expense', 'debit'],
        ];
        foreach ($accounts as $code => [$type, $nature]) {
            Account::firstOrCreate(['code' => $code], [
                'name' => "Cuenta {$code}", 'account_type' => $type, 'nature' => $nature,
                'level' => 2, 'currency' => 'MXN', 'is_postable' => true, 'status' => 'active',
            ]);
        }
    }

    // ------------------------------------------------------------------
    // G1 idempotencia
    // ------------------------------------------------------------------

    public function test_same_key_twice_returns_same_fractionation_without_new_movements(): void
    {
        [$source, $dest] = $this->bulkPair(40, 2);
        $this->stock($source, 100, 30);
        $key = 'c0ffee00-1111-4222-8333-444455556666';
        $payload = $this->payload($source, $dest, 10, ['idempotency_key' => $key]);

        $first = $this->execute($payload);
        $first->assertStatus(201)->assertJsonPath('meta.replayed', false);

        $second = $this->execute($payload);
        $second->assertStatus(200)->assertJsonPath('meta.replayed', true);

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('data.folioNumber'), $second->json('data.folioNumber'));
        $this->assertSame(1, Fractionation::count());
        $this->assertSame(2, InventoryMovement::where('reference_type', 'fractionation')->count());
        $this->assertEquals(90, (float) Stock::where('product_id', $source->id)->value('quantity'));
        $this->assertEquals(1, FolioSequence::where('document_type', 'fractionation')->value('current_sequence'));

        $fractionation = Fractionation::first();
        $this->assertSame($key, $fractionation->idempotency_key);
        $this->assertSame("fractionation:{$key}:exit", InventoryMovement::find($fractionation->exit_movement_id)->idempotency_key);
        $this->assertSame("fractionation:{$key}:entry", InventoryMovement::find($fractionation->entry_movement_id)->idempotency_key);
        $this->assertSame($fractionation->id, InventoryMovement::find($fractionation->exit_movement_id)->reference_id);
    }

    public function test_same_key_with_different_data_is_a_conflict(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 100, 30);

        $this->execute($this->payload($source, $dest, 10, ['idempotency_key' => 'k-1']))->assertStatus(201);
        $this->execute($this->payload($source, $dest, 12, ['idempotency_key' => 'k-1']))
            ->assertStatus(409)
            ->assertJsonPath('errors.0.code', 'idempotency_conflict');

        $this->assertSame(1, Fractionation::count());
    }

    public function test_without_key_each_post_is_a_new_fractionation(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 100, 30);

        $this->execute($this->payload($source, $dest, 1))->assertStatus(201);
        $this->execute($this->payload($source, $dest, 1))->assertStatus(201);

        $this->assertSame(2, Fractionation::count());
    }

    public function test_race_detected_by_locked_recheck_returns_existing(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 100, 30);
        // Primer fraccionamiento directo al servicio: el controlador queda
        // cacheado en la ruta tras la primera peticion HTTP y no tomaria el doble.
        app(FractionationService::class)->execute($this->payload($source, $dest, 10, [
            'idempotency_key' => 'race-a',
            'user_id' => $this->admin->id,
        ]));

        // La consulta previa (sin candado) no la ve: simula que la otra peticion
        // confirmo justo despues. La consulta con candado si la encuentra.
        $this->app->instance(FractionationService::class, new class(app(InventoryMovementService::class)) extends FractionationService {
            protected function findByIdempotencyKey(string $key, bool $lock): ?Fractionation
            {
                return $lock ? parent::findByIdempotencyKey($key, $lock) : null;
            }
        });

        $this->execute($this->payload($source, $dest, 10, ['idempotency_key' => 'race-a']))
            ->assertStatus(200)
            ->assertJsonPath('meta.replayed', true);

        $this->assertSame(1, Fractionation::count());
        $this->assertEquals(90, (float) Stock::where('product_id', $source->id)->value('quantity'));
    }

    public function test_race_detected_by_unique_constraint_returns_existing(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 100, 30);
        // Primer fraccionamiento directo al servicio: el controlador queda
        // cacheado en la ruta tras la primera peticion HTTP y no tomaria el doble.
        app(FractionationService::class)->execute($this->payload($source, $dest, 10, [
            'idempotency_key' => 'race-b',
            'user_id' => $this->admin->id,
        ]));

        // Ninguna consulta la ve: el UNIQUE de la BD rechaza el insert y la
        // captura devuelve la existente; la transaccion revierte el doble descuento.
        $calls = new \ArrayObject(['n' => 0]);
        $this->app->instance(FractionationService::class, new class(app(InventoryMovementService::class), $calls) extends FractionationService {
            public function __construct(InventoryMovementService $movementService, private \ArrayObject $calls)
            {
                parent::__construct($movementService);
            }

            protected function findByIdempotencyKey(string $key, bool $lock): ?Fractionation
            {
                $this->calls['n']++;

                return $this->calls['n'] <= 2 ? null : parent::findByIdempotencyKey($key, $lock);
            }
        });

        $this->execute($this->payload($source, $dest, 10, ['idempotency_key' => 'race-b']))
            ->assertStatus(200)
            ->assertJsonPath('meta.replayed', true);

        $this->assertSame(3, $calls['n']);
        $this->assertSame(1, Fractionation::count());
        $this->assertSame(2, InventoryMovement::where('reference_type', 'fractionation')->count());
        $this->assertEquals(90, (float) Stock::where('product_id', $source->id)->value('quantity'));
    }

    // ------------------------------------------------------------------
    // G6 disponible, G7 errores y G13 permiso de simulacion
    // ------------------------------------------------------------------

    public function test_reserved_stock_is_not_available(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 10, 30, ['reserved_quantity' => 8]);

        $calc = $this->calculate($this->payload($source, $dest, 5))->assertOk();
        $this->assertEquals(2, $calc->json('data.available_stock'));
        $this->assertEquals(10, $calc->json('data.on_hand_stock'));
        $this->assertFalse($calc->json('data.has_enough_stock'));

        $this->execute($this->payload($source, $dest, 5))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'insufficient_stock')
            ->assertJsonPath('errors.0.status', '422')
            ->assertJsonStructure(['message', 'errors' => [['title', 'detail']]]);
        $this->assertSame(0, Fractionation::count());
    }

    public function test_calculate_allowed_with_index_permission(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 10, 30);
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $this->calculate($this->payload($source, $dest, 1), $viewer)->assertOk();
        $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/v1/fraccionamiento/execute', $this->payload($source, $dest, 1))
            ->assertStatus(403)
            ->assertJsonStructure(['message', 'errors' => [['status', 'title', 'detail']]]);
    }

    // ------------------------------------------------------------------
    // G2 costo y G3 contabilidad
    // ------------------------------------------------------------------

    public function test_destination_cost_absorbs_waste_and_averages_existing_stock(): void
    {
        [$source, $dest] = $this->bulkPair(40, 2);
        $this->stock($source, 100, 30);
        // Presentacion con existencia previa: 80 piezas a 0.70
        $this->stock($dest, 80, 0.70);

        $response = $this->execute($this->payload($source, $dest, 10))->assertStatus(201);

        // 10 L * 30 = 300; producido 10 * 40 * 0.98 = 392 -> 0.765306 por pieza
        $this->assertEquals(392, $response->json('data.producedQuantity'));
        $this->assertEqualsWithDelta(300 / 392, $response->json('data.destinationUnitCost'), 0.000001);
        $this->assertEquals(300, $response->json('data.totalCost'));

        $entry = InventoryMovement::find($response->json('data.entryMovementId'));
        $this->assertEqualsWithDelta(0.7653, $entry->unit_cost, 0.00001);
        $exit = InventoryMovement::find($response->json('data.exitMovementId'));
        $this->assertEqualsWithDelta($exit->quantity * $exit->unit_cost, $entry->quantity * $entry->unit_cost, 0.05);

        // Promedio ponderado: (80 * 0.70 + 392 * 0.7653) / 472
        $destStock = Stock::where('product_id', $dest->id)->first();
        $this->assertEquals(472, (float) $destStock->quantity);
        $this->assertEqualsWithDelta((80 * 0.70 + 392 * 0.7653) / 472, (float) $destStock->unit_cost, 0.0001);
    }

    public function test_source_without_cost_enters_zero_warns_and_keeps_existing_destination_cost(): void
    {
        [$source, $dest] = $this->bulkPair(40, 0);
        $this->stock($source, 100, 0);
        $this->stock($dest, 10, 1.25);

        $calc = $this->calculate($this->payload($source, $dest, 1))->assertOk();
        $this->assertFalse($calc->json('data.has_cost'));
        $this->assertNotEmpty($calc->json('data.warnings'));

        $response = $this->execute($this->payload($source, $dest, 1))->assertStatus(201);
        $this->assertNotEmpty($response->json('meta.warnings'));
        $this->assertEquals(0, $response->json('data.destinationUnitCost'));
        $this->assertEquals(1.25, (float) Stock::where('product_id', $dest->id)->value('unit_cost'));
    }

    public function test_product_cost_is_used_when_stock_has_no_cost(): void
    {
        [$source, $dest] = $this->bulkPair(10, 0);
        $source->update(['cost' => 20]);
        $this->stock($source, 100, 0);

        $response = $this->execute($this->payload($source, $dest, 1))->assertStatus(201);
        $this->assertEquals(2, $response->json('data.destinationUnitCost'));
        $this->assertEquals(20, InventoryMovement::find($response->json('data.exitMovementId'))->unit_cost);
    }

    public function test_fractionation_posts_no_cogs_or_accrual(): void
    {
        $this->createGLInfrastructure();
        [$source, $dest] = $this->bulkPair(40, 2);
        $this->stock($source, 100, 30);

        // Control: una salida normal si postea (la infraestructura GL funciona)
        $before = JournalEntry::count();
        app(InventoryMovementService::class)->createExit([
            'product_id' => $source->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 1,
            'user_id' => $this->admin->id,
            'reference_type' => 'sale',
            'quality_checked' => true,
        ]);
        $this->assertSame($before + 1, JournalEntry::count());

        $before = JournalEntry::count();
        $response = $this->execute($this->payload($source, $dest, 10))->assertStatus(201);
        $this->assertSame($before, JournalEntry::count());

        foreach (['exitMovementId', 'entryMovementId'] as $field) {
            $movement = InventoryMovement::find($response->json("data.{$field}"));
            $this->assertSame('posted', $movement->gl_posting_status);
            $this->assertNull($movement->gl_journal_entry_id);
            $this->assertStringContainsString('Fraccionamiento', $movement->gl_posting_notes);
        }
    }

    // ------------------------------------------------------------------
    // G4 lotes
    // ------------------------------------------------------------------

    public function test_batch_is_discounted_and_destination_batch_inherits_lot(): void
    {
        [$source, $dest] = $this->bulkPair(40, 2);
        $this->stock($source, 100, 30);
        $batch = $this->batch($source, 60, ['lot_number' => 'MP-7781', 'expiration_date' => '2027-05-31']);

        $response = $this->execute($this->payload($source, $dest, 10, ['product_batch_id' => $batch->id]))->assertStatus(201);
        $folio = $response->json('data.folioNumber');

        $batch->refresh();
        $this->assertEquals(50, $batch->current_quantity);
        $this->assertSame('active', $batch->status);

        $destBatch = ProductBatch::find($response->json('data.destinationBatchId'));
        $this->assertNotNull($destBatch);
        $this->assertSame($dest->id, $destBatch->product_id);
        $this->assertSame($folio, $destBatch->batch_number);
        $this->assertSame('MP-7781', $destBatch->lot_number);
        $this->assertSame('2027-05-31', $destBatch->expiration_date->format('Y-m-d'));
        $this->assertEquals(392, $destBatch->initial_quantity);
        $this->assertEquals(392, $destBatch->current_quantity);
        $this->assertEqualsWithDelta(0.7653, $destBatch->unit_cost, 0.00001);
        $this->assertSame($batch->id, $destBatch->metadata['source_batch_id']);
        $this->assertSame($response->json('data.id'), $destBatch->metadata['fractionation_id']);

        $exit = InventoryMovement::find($response->json('data.exitMovementId'));
        $entry = InventoryMovement::find($response->json('data.entryMovementId'));
        $this->assertSame($batch->id, $exit->product_batch_id);
        $this->assertSame($destBatch->id, $exit->destination_batch_id);
        $this->assertSame($destBatch->id, $entry->product_batch_id);

        $fractionation = Fractionation::find($response->json('data.id'));
        $this->assertSame($batch->id, $fractionation->source_batch_id);
        $this->assertSame($destBatch->id, $fractionation->destination_batch_id);
    }

    public function test_batch_fully_used_becomes_consumed(): void
    {
        [$source, $dest] = $this->bulkPair(4, 0);
        $this->stock($source, 100, 30);
        $batch = $this->batch($source, 5);

        $this->execute($this->payload($source, $dest, 5, ['product_batch_id' => $batch->id]))->assertStatus(201);

        $batch->refresh();
        $this->assertEquals(0, $batch->current_quantity);
        $this->assertSame('consumed', $batch->status);
    }

    public function test_batch_is_required_when_source_has_active_batches(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 100, 30);
        $this->batch($source, 60);

        $calc = $this->calculate($this->payload($source, $dest, 10))->assertOk();
        $this->assertTrue($calc->json('data.batch_required'));

        $this->execute($this->payload($source, $dest, 10))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'batch_required');
    }

    public function test_batch_without_enough_quantity_is_rejected(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 100, 30);
        $batch = $this->batch($source, 3);

        $this->execute($this->payload($source, $dest, 10, ['product_batch_id' => $batch->id]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'batch_insufficient');
        $this->assertEquals(3, $batch->fresh()->current_quantity);
    }

    public function test_batch_of_another_product_is_rejected(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 100, 30);
        $other = Product::factory()->create();
        $batch = $this->batch($other, 60);

        $this->execute($this->payload($source, $dest, 10, ['product_batch_id' => $batch->id]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'batch_product_mismatch');
    }

    public function test_expired_batch_is_rejected(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 100, 30);
        $batch = $this->batch($source, 60, ['expiration_date' => now()->subDay()->format('Y-m-d')]);

        $this->execute($this->payload($source, $dest, 10, ['product_batch_id' => $batch->id]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'batch_expired');
        $this->assertStringContainsString('caducó el ' . now()->subDay()->format('d/m/Y'), $this->execute($this->payload($source, $dest, 10, ['product_batch_id' => $batch->id]))->json('errors.0.detail'));
    }

    /**
     * D10: un lote en cuarentena no se fracciona ni se sugiere, y la vista
     * previa explica por que (el formulario lo muestra junto al lote).
     */
    public function test_quarantined_batch_is_explained_not_suggested_and_rejected(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 500, 30);
        $quarantined = $this->batch($source, 100, ['expiration_date' => now()->addDays(10)->format('Y-m-d'), 'quality_status' => 'quarantine']);
        $good = $this->batch($source, 100, ['expiration_date' => now()->addMonths(3)->format('Y-m-d')]);

        $calc = $this->calculate($this->payload($source, $dest, 10))->assertOk();
        $this->assertSame($good->id, $calc->json('data.suggested_batch_id'));
        $row = collect($calc->json('data.batches'))->firstWhere('id', $quarantined->id);
        $this->assertFalse($row['is_usable']);
        $this->assertSame('el lote está en cuarentena', $row['unavailable_reason']);
        $this->assertTrue(collect($calc->json('data.batches'))->firstWhere('id', $good->id)['is_usable']);

        $response = $this->execute($this->payload($source, $dest, 10, ['product_batch_id' => $quarantined->id]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'batch_quality_blocked');
        $this->assertStringContainsString('cuarentena', $response->json('errors.0.detail'));
    }

    public function test_calculate_suggests_fefo_batch_skipping_expired_and_short_ones(): void
    {
        [$source, $dest] = $this->bulkPair();
        $this->stock($source, 500, 30);
        $this->batch($source, 100, ['expiration_date' => now()->subDays(3)->format('Y-m-d')]); // caducado
        $this->batch($source, 2, ['expiration_date' => now()->addMonth()->format('Y-m-d')]);  // no alcanza
        $fefo = $this->batch($source, 100, ['expiration_date' => now()->addMonths(2)->format('Y-m-d')]);
        $this->batch($source, 100, ['expiration_date' => now()->addMonths(6)->format('Y-m-d')]);

        $calc = $this->calculate($this->payload($source, $dest, 10))->assertOk();

        $this->assertSame($fefo->id, $calc->json('data.suggested_batch_id'));
        $this->assertSame($fefo->id, $calc->json('data.product_batch_id'));
        $this->assertCount(4, $calc->json('data.batches'));
        $this->assertTrue($calc->json('data.batches.0.is_expired'));
        $this->assertTrue($calc->json('data.has_enough_stock'));
    }

    // ------------------------------------------------------------------
    // G5 ubicacion
    // ------------------------------------------------------------------

    public function test_location_is_required_with_several_stock_rows(): void
    {
        [$source, $dest] = $this->bulkPair();
        $locA = WarehouseLocation::factory()->create(['warehouse_id' => $this->warehouse->id]);
        $locB = WarehouseLocation::factory()->create(['warehouse_id' => $this->warehouse->id]);
        $this->stock($source, 30, 30, ['warehouse_location_id' => $locA->id]);
        $this->stock($source, 50, 30, ['warehouse_location_id' => $locB->id]);

        $calc = $this->calculate($this->payload($source, $dest, 10))->assertOk();
        $this->assertTrue($calc->json('data.location_required'));
        $this->assertEquals(80, $calc->json('data.available_stock'));
        $this->assertCount(2, $calc->json('data.locations'));
        $this->assertFalse($calc->json('data.can_execute'));

        $this->execute($this->payload($source, $dest, 10))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'location_required');

        $response = $this->execute($this->payload($source, $dest, 10, ['warehouse_location_id' => $locB->id]))->assertStatus(201);
        $this->assertEquals(40, (float) Stock::where('warehouse_location_id', $locB->id)->where('product_id', $source->id)->value('quantity'));
        $this->assertEquals(30, (float) Stock::where('warehouse_location_id', $locA->id)->where('product_id', $source->id)->value('quantity'));
        $this->assertSame($locB->id, InventoryMovement::find($response->json('data.exitMovementId'))->warehouse_location_id);
        $this->assertSame($locB->id, InventoryMovement::find($response->json('data.entryMovementId'))->warehouse_location_id);
        $this->assertSame($locB->id, Fractionation::find($response->json('data.id'))->warehouse_location_id);
    }

    // ------------------------------------------------------------------
    // G8 piezas enteras y factor de 6 decimales
    // ------------------------------------------------------------------

    public function test_whole_units_rounds_down_and_leaves_leftover_in_bulk(): void
    {
        // 1 L = 0.264172 galones
        [$source, $dest] = $this->bulkPair(0.264172, 0, true);
        $this->stock($source, 100, 30);

        $calc = $this->calculate($this->payload($source, $dest, 7))->assertOk();
        $this->assertEquals(1, $calc->json('data.produced_quantity'));
        $this->assertEquals(3.7855, $calc->json('data.consumed_quantity'));
        $this->assertEquals(3.2145, $calc->json('data.leftover_quantity'));

        $response = $this->execute($this->payload($source, $dest, 7))->assertStatus(201);
        $this->assertEquals(1, $response->json('data.producedQuantity'));
        $this->assertEquals(3.7855, $response->json('data.sourceQuantity'));
        $this->assertEquals(7, $response->json('data.requestedQuantity'));
        $this->assertEqualsWithDelta(100 - 3.7855, (float) Stock::where('product_id', $source->id)->value('quantity'), 0.00001);
        $this->assertEquals(0.264172, Fractionation::first()->conversion_factor_used);
    }

    public function test_whole_units_with_waste_keeps_exact_consumption(): void
    {
        // 100 L a 25 ml con 2 % de merma: 3,920 piezas exactas, salen los 100 L
        [$source, $dest] = $this->bulkPair(40, 2, true);
        $this->stock($source, 200, 30);

        $response = $this->execute($this->payload($source, $dest, 100))->assertStatus(201);
        $this->assertEquals(3920, $response->json('data.producedQuantity'));
        $this->assertEquals(100, $response->json('data.sourceQuantity'));
        $this->assertEquals(80, $response->json('data.wasteQuantity'));
    }

    public function test_whole_units_not_enough_for_one_piece(): void
    {
        [$source, $dest] = $this->bulkPair(0.264172, 0, true);
        $this->stock($source, 100, 30);

        $this->execute($this->payload($source, $dest, 1))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'insufficient_for_unit');
    }

    public function test_plan_production_formulas(): void
    {
        $conversion = new ProductConversion(['conversion_factor' => 40, 'waste_percentage' => 2, 'whole_units' => false]);
        $this->assertEquals(
            ['requested' => 5.0, 'consumed' => 5.0, 'produced' => 196.0, 'waste' => 4.0, 'leftover' => 0.0],
            $conversion->planProduction(5)
        );

        $whole = new ProductConversion(['conversion_factor' => 40, 'waste_percentage' => 2, 'whole_units' => true]);
        // 0.03 L * 39.2 = 1.176 -> 1 pieza; consumido = 1 / 39.2 = 0.02551... -> 0.0256
        $plan = $whole->planProduction(0.03);
        $this->assertEquals(1, $plan['produced']);
        $this->assertEquals(0.0256, $plan['consumed']);
        $this->assertEquals(0.0044, $plan['leftover']);
        $this->assertGreaterThanOrEqual(1, $plan['consumed'] * 39.2);
    }

    public function test_conversion_factor_keeps_six_decimals_and_whole_units_via_jsonapi(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'product-conversions.store', 'guard_name' => 'api']);
        $this->admin->givePermissionTo($perm);
        $source = Product::factory()->create();
        $dest = Product::factory()->create();

        $post = fn (float $factor) => $this->actingAs($this->admin, 'sanctum')
            ->jsonApi()
            ->expects('product-conversions')
            ->withData([
                'type' => 'product-conversions',
                'attributes' => [
                    'sourceProductId' => $source->id,
                    'destinationProductId' => $dest->id,
                    'conversionFactor' => $factor,
                    'wholeUnits' => true,
                ],
            ])
            ->post('/api/v1/product-conversions');

        $post(0.2641721)->assertStatus(422);
        $response = $post(0.264172)->assertStatus(201);

        $this->assertTrue($response->json('data.attributes.wholeUnits'));
        $conversion = ProductConversion::first();
        $this->assertEquals(0.264172, $conversion->conversion_factor);
        $this->assertTrue($conversion->whole_units);
    }

    // ------------------------------------------------------------------
    // G12 detalle
    // ------------------------------------------------------------------

    public function test_detail_exposes_batches_costs_and_movements(): void
    {
        Permission::firstOrCreate(['name' => 'fractionations.show', 'guard_name' => 'api']);
        $this->admin->givePermissionTo('fractionations.show');
        [$source, $dest] = $this->bulkPair(40, 2);
        $this->stock($source, 100, 30);
        $batch = $this->batch($source, 60);
        $id = $this->execute($this->payload($source, $dest, 10, ['product_batch_id' => $batch->id]))->json('data.id');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->jsonApi()
            ->expects('fractionations')
            ->includePaths('sourceBatch', 'destinationBatch', 'exitMovement', 'entryMovement')
            ->get("/api/v1/fractionations/{$id}")
            ->assertOk();

        $this->assertEquals(300, $response->json('data.attributes.totalCost'));
        $this->assertEquals(10, $response->json('data.attributes.requestedQuantity'));
        $this->assertSame((string) $batch->id, $response->json('data.relationships.sourceBatch.data.id'));
        $types = collect($response->json('included'))->pluck('type')->unique()->sort()->values()->all();
        $this->assertSame(['inventory-movements', 'product-batches'], $types);
    }
}
