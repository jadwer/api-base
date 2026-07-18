<?php

namespace Modules\Purchase\Tests\Integration;

use Laravel\Sanctum\Sanctum;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\APInvoice;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Tests\TestCase;

/**
 * Fase 2.7: test de INVARIANTE de negocio (no de fachada) para la compra cancelada.
 *
 * El bug que fija (Patron 2 de la auditoria 2026-07): cancelar una OC ya recibida
 * dejaba stock fantasma y una APInvoice viva con su asiento GL posteado. El refactor
 * agrego el endpoint POST /purchase-orders/{id}/cancel que dispara
 * PurchaseOrderCancelled(previousStatus); Inventory revierte el stock (exit con
 * idempotency_key "purchase_cancel:{poId}:item:{itemId}") y Finance anula la
 * APInvoice y revierte su asiento.
 *
 * Reglas anti-fachada que respeta este archivo:
 * - Sin Event::fake ni eventos disparados a mano: se ejercitan los endpoints reales
 *   (receive y cancel) y los listeners corren de verdad.
 * - La APInvoice y las entradas de stock NACEN del flujo de recepcion real, no de
 *   factories.
 * - Los asserts van contra la BASE (stock, inventory_movements, ap_invoices,
 *   journal_entries/journal_lines), no contra el JSON de la respuesta.
 */
class PurchaseOrderCancelInvariantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Igual que PurchaseOrderInventoryIntegrationTest: limpiar movimientos
        // sembrados con reference_type purchase/purchase_cancel para que los
        // conteos por reference_id no choquen con ids aleatorios del seeder.
        InventoryMovement::whereIn('reference_type', ['purchase', 'purchase_cancel'])->delete();

        // FiscalPeriodSeeder solo siembra 2025; el posting GL exige un periodo
        // abierto para HOY. Mismo patron que ARInvoiceGLPostingTest (setup de
        // entorno, no del estado bajo prueba).
        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            [
                'name' => now()->format('Y-m'),
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
            ]
        );
    }

    /**
     * Crea una OC aprobada con items concretos. Todo lo demas (stock, APInvoice,
     * asientos) debe salir del flujo real de recepcion, nunca de aqui.
     *
     * @param array $lines [['quantity' => x, 'unit_price' => y], ...]
     * @return array{0: PurchaseOrder, 1: Warehouse, 2: array<int, array{product: Product, item: PurchaseOrderItem}>}
     */
    private function makeApprovedOrder(array $lines, string $status = 'approved'): array
    {
        $warehouse = Warehouse::factory()->create(['is_active' => true]);
        $supplier = Contact::factory()->create(['is_supplier' => true]);

        $totalAmount = collect($lines)->sum(fn ($l) => $l['quantity'] * $l['unit_price']);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'contact_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => $status,
            // Debajo del umbral de aprobacion (50k) para no requerir approvals.
            'total_amount' => $totalAmount,
        ]);

        $created = [];
        foreach ($lines as $line) {
            $product = Product::factory()->create();
            $item = PurchaseOrderItem::factory()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $product->id,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount' => 0,
                'subtotal' => $line['quantity'] * $line['unit_price'],
                'total' => $line['quantity'] * $line['unit_price'],
                'received_quantity' => 0,
            ]);
            $created[] = ['product' => $product, 'item' => $item];
        }

        return [$purchaseOrder, $warehouse, $created];
    }

    private function stockQty(int $productId, int $warehouseId): float
    {
        return (float) Stock::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');
    }

    /**
     * Recibe la OC completa por el endpoint real, enviando los items EXPLICITOS.
     *
     * Nota (hallazgo 2026-07-18): el mismo endpoint SIN body deberia recibir todo,
     * pero el fallback del controller precarga purchaseOrderItems y
     * PurchaseOrder::isFullyReceived() relee esa relacion STALE, asi que la OC
     * nunca pasa a 'received' (ver el test dedicado abajo). Los invariantes de
     * cancelacion usan el camino con items explicitos, que si completa.
     */
    private function receiveInFull(PurchaseOrder $po, array $lines): void
    {
        $payload = [
            'items' => collect($lines)->map(fn ($l) => [
                'id' => $l['item']->id,
                'quantity' => (float) $l['item']->quantity,
            ])->values()->all(),
        ];

        $this->postJson("/api/v1/purchase-orders/{$po->id}/receive", $payload)->assertStatus(200);
    }

    /**
     * Camino feliz completo: recibir por el endpoint real sube stock y crea la
     * APInvoice; cancelar por el endpoint real regresa el stock al valor previo,
     * anula la APInvoice y postea un asiento de reversa que cuadra.
     */
    public function test_cancelling_received_order_reverts_stock_voids_ap_invoice_and_balances_gl(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        [$po, $warehouse, $lines] = $this->makeApprovedOrder([
            ['quantity' => 10, 'unit_price' => 50.00],
            ['quantity' => 5, 'unit_price' => 100.00],
        ]);

        $baseline = [];
        foreach ($lines as $i => $line) {
            $baseline[$i] = $this->stockQty($line['product']->id, $warehouse->id);
        }

        // ---- Paso 1: recepcion por el CAMINO REAL (endpoint receive, items explicitos) ----
        $this->receiveInFull($po, $lines);

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'received']);

        // El stock SUBIO por lo recibido.
        $this->assertEquals($baseline[0] + 10, $this->stockQty($lines[0]['product']->id, $warehouse->id));
        $this->assertEquals($baseline[1] + 5, $this->stockQty($lines[1]['product']->id, $warehouse->id));

        // La APInvoice existe y salio del flujo (listener de PurchaseOrderReceived),
        // posteada y con asiento GL vinculado.
        $apInvoice = APInvoice::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($apInvoice, 'La recepcion debe crear la APInvoice (flujo real, no factory)');
        $this->assertEquals('posted', $apInvoice->status);
        $this->assertNotNull($apInvoice->journal_entry_id, 'La APInvoice debe nacer con asiento GL');
        $this->assertEqualsWithDelta(1000.0, (float) $apInvoice->total_amount, 0.01);

        $originalEntry = JournalEntry::find($apInvoice->journal_entry_id);
        $this->assertEquals('posted', $originalEntry->status);

        // ---- Paso 2: cancelar por el CAMINO REAL (endpoint cancel) ----
        $this->postJson("/api/v1/purchase-orders/{$po->id}/cancel", [
            'reason' => 'Invariante: cancelacion post-recepcion',
        ])->assertStatus(200);

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'cancelled']);

        // Invariante 1: el stock REGRESA al valor previo a la recepcion.
        $this->assertEquals(
            $baseline[0],
            $this->stockQty($lines[0]['product']->id, $warehouse->id),
            'El stock del producto 1 debe volver al valor previo a la OC'
        );
        $this->assertEquals(
            $baseline[1],
            $this->stockQty($lines[1]['product']->id, $warehouse->id),
            'El stock del producto 2 debe volver al valor previo a la OC'
        );

        // Invariante 2: existe el movimiento exit de reversa con la idempotency_key
        // natural por item, trazable a la OC.
        foreach ($lines as $line) {
            $key = "purchase_cancel:{$po->id}:item:{$line['item']->id}";
            $this->assertDatabaseHas('inventory_movements', [
                'reference_type' => 'purchase_cancel',
                'reference_id' => $po->id,
                'product_id' => $line['product']->id,
                'movement_type' => 'exit',
                'idempotency_key' => $key,
                'status' => 'completed',
            ]);

            $exit = InventoryMovement::where('idempotency_key', $key)->first();
            $this->assertEquals(
                (float) $line['item']->quantity,
                (float) $exit->quantity,
                'La reversa debe salir por lo RECIBIDO del item'
            );
        }

        // Invariante 3: la APInvoice queda anulada.
        $this->assertDatabaseHas('ap_invoices', ['id' => $apInvoice->id, 'status' => 'voided']);
        $this->assertNotNull($apInvoice->fresh()->voided_at);

        // Invariante 4: el asiento de reversa existe, esta posteado y CUADRA
        // (SUM debe == SUM haber) espejeando el asiento original.
        $reversal = JournalEntry::where('reversal_of_id', $originalEntry->id)->first();
        $this->assertNotNull($reversal, 'Debe existir el asiento de reversa del asiento AP original');
        $this->assertEquals('posted', $reversal->status);

        $reversalDebit = (float) JournalLine::where('journal_entry_id', $reversal->id)->sum('debit');
        $reversalCredit = (float) JournalLine::where('journal_entry_id', $reversal->id)->sum('credit');
        $this->assertEqualsWithDelta($reversalDebit, $reversalCredit, 0.01, 'La reversa debe cuadrar');
        $this->assertEqualsWithDelta(
            (float) $apInvoice->total_amount,
            $reversalDebit,
            0.01,
            'La reversa debe ser por el total de la APInvoice'
        );

        // Espejo por cuenta: lo que el original cargo, la reversa lo abona (y viceversa).
        foreach ($originalEntry->journalLines as $line) {
            $mirror = JournalLine::where('journal_entry_id', $reversal->id)
                ->where('account_id', $line->account_id)
                ->first();
            $this->assertNotNull($mirror);
            $this->assertEqualsWithDelta((float) $line->debit, (float) $mirror->credit, 0.01);
            $this->assertEqualsWithDelta((float) $line->credit, (float) $mirror->debit, 0.01);
        }
    }

    /**
     * Idempotencia: cancelar dos veces no duplica reversas de stock ni asientos.
     * El segundo cancel es rechazado (422) y la base queda identica.
     */
    public function test_cancelling_twice_does_not_duplicate_reversals(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        [$po, $warehouse, $lines] = $this->makeApprovedOrder([
            ['quantity' => 10, 'unit_price' => 50.00],
        ]);
        $product = $lines[0]['product'];
        $baseline = $this->stockQty($product->id, $warehouse->id);

        $this->receiveInFull($po, $lines);
        $this->postJson("/api/v1/purchase-orders/{$po->id}/cancel")->assertStatus(200);

        $exitsAfterFirst = InventoryMovement::where('reference_type', 'purchase_cancel')
            ->where('reference_id', $po->id)
            ->count();
        $this->assertEquals(1, $exitsAfterFirst);

        $apInvoice = APInvoice::where('purchase_order_id', $po->id)->firstOrFail();
        $reversalsAfterFirst = JournalEntry::where('reversal_of_id', $apInvoice->journal_entry_id)->count();
        $this->assertEquals(1, $reversalsAfterFirst);

        // Segundo cancel: rechazado por estado invalido.
        $this->postJson("/api/v1/purchase-orders/{$po->id}/cancel")->assertStatus(422);

        // La base NO cambio: ni doble exit, ni doble reversa, ni stock negativo.
        $this->assertEquals(
            1,
            InventoryMovement::where('reference_type', 'purchase_cancel')
                ->where('reference_id', $po->id)
                ->count(),
            'El segundo cancel no debe duplicar la reversa de stock'
        );
        $this->assertEquals(
            1,
            JournalEntry::where('reversal_of_id', $apInvoice->journal_entry_id)->count(),
            'El segundo cancel no debe duplicar el asiento de reversa'
        );
        $this->assertEquals($baseline, $this->stockQty($product->id, $warehouse->id));
        $this->assertEquals(1, APInvoice::where('purchase_order_id', $po->id)->count());
        $this->assertDatabaseHas('ap_invoices', ['id' => $apInvoice->id, 'status' => 'voided']);
    }

    /**
     * Cancelar una OC pending (nunca recibida) NO toca stock, no crea reversas
     * y no deja rastro en Finance (no hubo entrada que revertir).
     */
    public function test_cancelling_pending_order_touches_nothing(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        [$po, $warehouse, $lines] = $this->makeApprovedOrder(
            [['quantity' => 10, 'unit_price' => 50.00]],
            'pending'
        );
        $product = $lines[0]['product'];
        $baseline = $this->stockQty($product->id, $warehouse->id);

        $this->postJson("/api/v1/purchase-orders/{$po->id}/cancel", [
            'reason' => 'Cancelada antes de recibir',
        ])->assertStatus(200);

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'status' => 'cancelled']);

        // Ni entrada ni reversa: cero movimientos ligados a la OC.
        $this->assertEquals(
            0,
            InventoryMovement::whereIn('reference_type', ['purchase', 'purchase_cancel'])
                ->where('reference_id', $po->id)
                ->count(),
            'Una OC nunca recibida no debe generar movimientos de inventario al cancelarse'
        );

        // El stock no se movio.
        $this->assertEquals($baseline, $this->stockQty($product->id, $warehouse->id));

        // No se creo (ni anulo) ninguna APInvoice.
        $this->assertDatabaseMissing('ap_invoices', ['purchase_order_id' => $po->id]);
    }

    /**
     * Invariante del contrato documentado del endpoint: POST receive SIN items
     * recibe todo lo pendiente; al llegar al 100% la OC debe quedar 'received'
     * y debe existir la APInvoice.
     *
     * HALLAZGO (2026-07-18, test dejado rojo a proposito): el fallback del
     * controller precarga $purchaseOrder->purchaseOrderItems para armar los items;
     * PurchaseOrder::receive() actualiza los items con instancias FRESCAS
     * ($this->purchaseOrderItems()->find(...)), pero isFullyReceived() ve
     * relationLoaded()==true y relee la coleccion STALE (received_quantity=0),
     * asi que la OC se queda en 'approved': entra stock pero nunca se dispara
     * PurchaseOrderReceived ni se crea la APInvoice. El camino con items
     * explicitos no precarga la relacion y si completa.
     */
    public function test_receive_without_explicit_items_completes_the_order_and_creates_ap_invoice(): void
    {
        Sanctum::actingAs($this->getAdminUser());

        [$po, $warehouse, $lines] = $this->makeApprovedOrder([
            ['quantity' => 10, 'unit_price' => 50.00],
        ]);

        // Camino documentado: sin items en el body se recibe todo lo pendiente.
        $this->postJson("/api/v1/purchase-orders/{$po->id}/receive")->assertStatus(200);

        // El stock SI entro (la tanda se proceso)...
        $this->assertDatabaseHas('inventory_movements', [
            'reference_type' => 'purchase',
            'reference_id' => $po->id,
            'product_id' => $lines[0]['product']->id,
            'movement_type' => 'entry',
        ]);
        $this->assertEquals(10.0, (float) $lines[0]['item']->fresh()->received_quantity);

        // ...y al estar recibida al 100% la OC DEBE quedar 'received' con APInvoice.
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'status' => 'received',
        ]);
        $this->assertDatabaseHas('ap_invoices', ['purchase_order_id' => $po->id]);
    }
}
