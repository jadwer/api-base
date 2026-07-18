<?php

namespace Modules\Sales\Tests\Integration;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\OrderStatusService;
use Tests\TestCase;

/**
 * Fase 2.7: test de INVARIANTE de la reversa del ciclo de venta (no de fachada).
 *
 * Camino de cancelacion probado y por que: la maquina de estados NO permite
 * delivered -> cancelled (validTransitions['delivered'] = ['completed', 'returned']).
 * El camino REAL para revertir una venta ya entregada y facturada es 'returned',
 * que en OrderStatusService::handleStatusChange dispara SalesOrderCancelled y con
 * el a Finance\SalesOrderCancelledListener (anula la ARInvoice, revierte el asiento
 * y repone el stock entregado). Ese es el camino que se ejercita aqui, por el
 * servicio real y pasando por todos los estados intermedios validos.
 *
 * Invariantes contra la BASE:
 * - La ARInvoice queda voided y la orden vuelve a invoicing_status = not_invoiced.
 * - Existe el asiento de reversa (reversal_of del asiento de la factura), cuadra
 *   y va por el mismo importe (DR 4101 / CR 1104, espejo del original).
 * - El stock fisico vuelve al valor inicial via movimientos de compensacion con
 *   idempotency_key sales_cancel.
 * - Revertir dos veces no duplica reversas (la maquina bloquea el segundo intento).
 */
class SalesOrderCancelInvariantTest extends TestCase
{
    private const INITIAL_STOCK = 10.0;
    private const SOLD_QTY = 4.0;
    private const UNIT_COST = 50.0;
    private const ORDER_SUBTOTAL = 1000.0;
    private const ORDER_TAX = 160.0;
    private const ORDER_TOTAL = 1160.0;
    private const UNIT_PRICE = 290.0;

    protected function setUp(): void
    {
        parent::setUp();

        // Periodo fiscal abierto: lo exigen el posting del COGS, el de la factura
        // y la reversa (reverseJournalEntry valida periodo).
        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            [
                'name' => now()->format('Y-m'),
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
            ]
        );

        // Evitar colisiones con reference_ids aleatorios del seeder de movimientos.
        InventoryMovement::whereIn('reference_type', ['sales_order', 'sales_cancel'])->delete();
    }

    /**
     * Ciclo real hasta delivered + facturada: producto con stock costeado, orden
     * draft, transiciones draft -> confirmed -> processing -> shipped -> delivered.
     * La factura la crea el listener del evento de entrega (no se factura a mano).
     * Devuelve [order, invoice, product].
     */
    private function deliverInvoicedOrder(): array
    {
        $warehouse = Warehouse::factory()->create(['is_active' => true]);

        $customer = Contact::factory()->customer()->create([
            'status' => 'active',
            'credit_limit' => 500000,
            'current_credit' => 0,
            'payment_terms' => 30,
        ]);

        $product = Product::factory()->create(['cost' => self::UNIT_COST]);

        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => null,
            'quantity' => self::INITIAL_STOCK,
            'reserved_quantity' => 0,
            'unit_cost' => self::UNIT_COST,
        ]);

        $order = SalesOrder::factory()->draft()->create([
            'contact_id' => $customer->id,
            'subtotal' => self::ORDER_SUBTOTAL,
            'tax_amount' => self::ORDER_TAX,
            'discount_total' => 0,
            'total_amount' => self::ORDER_TOTAL,
            'metadata' => ['warehouse_id' => $warehouse->id],
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => self::SOLD_QTY,
            'unit_price' => self::UNIT_PRICE,
            'discount' => 0,
        ]);

        $service = app(OrderStatusService::class);
        foreach (['confirmed', 'processing', 'shipped', 'delivered'] as $status) {
            $order = $service->updateStatus($order, $status);
        }
        $order = $order->fresh();

        $invoice = ARInvoice::where('sales_order_id', $order->id)->first();
        $this->assertNotNull($invoice, 'Precondicion: la entrega debe haber facturado la orden');
        $this->assertEquals('posted', $invoice->status, 'Precondicion: la factura debe estar posted');

        return [$order, $invoice, $product];
    }

    private function returnOrder(SalesOrder $order): SalesOrder
    {
        // Camino real de reversa post-entrega: delivered -> returned (la maquina
        // prohibe delivered -> cancelled; ver docblock de la clase).
        return app(OrderStatusService::class)->updateStatus($order->fresh(), 'returned');
    }

    private function physicalStock(Product $product): float
    {
        return (float) Stock::where('product_id', $product->id)->sum('quantity');
    }

    public function test_return_voids_invoice_and_posts_balanced_reversal(): void
    {
        [$order, $invoice] = $this->deliverInvoicedOrder();

        $order = $this->returnOrder($order);

        // Invariante 1: la factura queda voided y la orden vuelve a not_invoiced.
        $invoice->refresh();
        $this->assertEquals('voided', $invoice->status, 'La reversa debe anular la ARInvoice');

        $order->refresh();
        $this->assertEquals('not_invoiced', $order->invoicing_status, 'La orden debe volver a not_invoiced');
        $this->assertEquals('cancelled', $order->financial_status);

        // Invariante 2: existe el asiento de reversa del asiento de la factura,
        // cuadra y es espejo por el mismo importe.
        $reversal = JournalEntry::where('reversal_of_id', $invoice->journal_entry_id)->first();
        $this->assertNotNull($reversal, 'Debe existir el asiento de reversa de la factura');
        $this->assertEquals('posted', $reversal->status, 'La reversa debe quedar posted');

        $debit = (float) $reversal->journalLines->sum('debit');
        $credit = (float) $reversal->journalLines->sum('credit');
        $this->assertEqualsWithDelta($debit, $credit, 0.01, 'El asiento de reversa debe cuadrar');
        $this->assertEqualsWithDelta(self::ORDER_TOTAL, $debit, 0.01, 'La reversa debe ir por el importe de la factura');

        // Espejo del original: ahora el cargo es a 4101 (Ventas) y el abono a 1104 (Clientes).
        $debitLine = $reversal->journalLines->first(fn ($l) => (float) $l->debit > 0);
        $creditLine = $reversal->journalLines->first(fn ($l) => (float) $l->credit > 0);
        $this->assertEquals('4101', Account::find($debitLine->account_id)?->code, 'La reversa carga 4101 (Ventas)');
        $this->assertEquals('1104', Account::find($creditLine->account_id)?->code, 'La reversa abona 1104 (Clientes)');
    }

    public function test_return_restores_delivered_stock_with_sales_cancel_movements(): void
    {
        [$order, , $product] = $this->deliverInvoicedOrder();

        // Sanity: la entrega SI desconto el stock fisico.
        $this->assertEquals(
            self::INITIAL_STOCK - self::SOLD_QTY,
            $this->physicalStock($product),
            'Precondicion: la entrega debe haber descontado el stock'
        );

        $exit = InventoryMovement::where('reference_type', 'sales_order')
            ->where('reference_id', $order->id)
            ->where('movement_type', 'exit')
            ->firstOrFail();

        $this->returnOrder($order);

        // Invariante 3a: existe el movimiento de compensacion (entry) con la clave
        // de idempotencia sales_cancel apuntando al exit que revierte.
        $compensation = InventoryMovement::where('reference_type', 'sales_cancel')
            ->where('movement_type', 'entry')
            ->where('idempotency_key', "sales_cancel:exit:{$exit->id}")
            ->first();
        $this->assertNotNull(
            $compensation,
            'La reversa debe crear el movimiento de compensacion sales_cancel del exit de la entrega'
        );
        $this->assertEquals(self::SOLD_QTY, (float) $compensation->quantity);

        // Invariante 3b: el stock fisico vuelve al valor inicial.
        $this->assertEquals(
            self::INITIAL_STOCK,
            $this->physicalStock($product),
            'La reversa debe reponer el stock fisico al valor inicial'
        );
    }

    public function test_returning_twice_does_not_duplicate_reversals(): void
    {
        [$order, $invoice, $product] = $this->deliverInvoicedOrder();

        $this->returnOrder($order);

        $reversalCount = fn () => JournalEntry::where('reversal_of_id', $invoice->journal_entry_id)->count();
        $compensationCount = fn () => InventoryMovement::where('reference_type', 'sales_cancel')->count();

        $this->assertEquals(1, $reversalCount(), 'La primera reversa debe existir una sola vez');
        $stockAfterFirst = $this->physicalStock($product);
        $compensationsAfterFirst = $compensationCount();

        // Invariante 4: el segundo intento lo bloquea la maquina (returned solo
        // transiciona a refunded) y NADA se duplica.
        try {
            $this->returnOrder($order);
            $this->fail('La maquina de estados debe impedir revertir dos veces');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Cannot transition', $e->getMessage());
        }

        $this->assertEquals(1, $reversalCount(), 'No debe duplicarse el asiento de reversa');
        $this->assertEquals(1, ARInvoice::where('sales_order_id', $order->id)->where('status', 'voided')->count());
        $this->assertEquals($compensationsAfterFirst, $compensationCount(), 'No deben duplicarse las compensaciones de stock');
        $this->assertEquals($stockAfterFirst, $this->physicalStock($product), 'El stock no debe moverse en el segundo intento');
    }
}
