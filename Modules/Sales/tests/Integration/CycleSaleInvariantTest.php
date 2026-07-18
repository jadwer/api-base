<?php

namespace Modules\Sales\Tests\Integration;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Services\EventReplayService;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Services\OrderStatusService;
use Tests\TestCase;

/**
 * Fase 2.7: test de INVARIANTE del ciclo feliz de venta (no de fachada).
 *
 * Ejercita el camino REAL de produccion: la orden nace en draft y se lleva a
 * delivered via OrderStatusService pasando por los estados intermedios validos
 * (draft -> confirmed -> processing -> shipped -> delivered). Nada de Event::fake,
 * nada de disparar eventos a mano, nada de montar Journal/ARInvoice en setUp.
 *
 * Invariantes que fija contra la BASE:
 * - El stock fisico baja exactamente la cantidad vendida.
 * - Existe la salida de inventario trazable (reference sales_order + idempotency_key).
 * - El asiento COGS existe, cuadra (debe == haber), importe > 0 y == qty * unit_cost,
 *   con cargo a 5101 (Costo de Ventas) y abono a 1108 (Almacen).
 * - La ARInvoice se crea SOLA al entregar (listener CreateARInvoiceForSalesOrder),
 *   queda posted, su total cuadra con la orden y su asiento cuadra (1104 / 4101).
 * - El cobro por el endpoint real de pagos AR deja balance 0 y status 'paid'.
 * - Reintentos (re-entrega bloqueada por la maquina, replay de eventos) NO duplican
 *   salidas ni facturas.
 */
class CycleSaleInvariantTest extends TestCase
{
    private const INITIAL_STOCK = 10.0;
    private const SOLD_QTY = 4.0;
    private const UNIT_COST = 50.0;
    private const EXPECTED_COGS = 200.0;   // SOLD_QTY * UNIT_COST
    private const ORDER_SUBTOTAL = 1000.0;
    private const ORDER_TAX = 160.0;
    private const ORDER_TOTAL = 1160.0;
    private const UNIT_PRICE = 290.0;      // SOLD_QTY * UNIT_PRICE = ORDER_TOTAL

    protected function setUp(): void
    {
        parent::setUp();

        // Periodo fiscal abierto: el GL posting (COGS, factura AR y pago) lo exige.
        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            [
                'name' => now()->format('Y-m'),
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
            ]
        );

        // Limpieza de movimientos sembrados con reference_type del ciclo de venta,
        // para que las busquedas por reference_id no colisionen con ids aleatorios
        // del seeder (mismo criterio que PurchaseOrderInventoryIntegrationTest).
        InventoryMovement::whereIn('reference_type', ['sales_order', 'sales_cancel'])->delete();
    }

    /**
     * Andamiaje UNICO del ciclo: producto con stock costeado, orden draft con un
     * item, y transiciones reales hasta delivered. Devuelve [order, product, item].
     */
    private function runDeliveredCycle(): array
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

        $item = SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => self::SOLD_QTY,
            'unit_price' => self::UNIT_PRICE,
            'discount' => 0,
        ]);

        // Camino real: la maquina de estados del servicio, sin saltos.
        $service = app(OrderStatusService::class);
        foreach (['confirmed', 'processing', 'shipped', 'delivered'] as $status) {
            $order = $service->updateStatus($order, $status);
        }

        return [$order->fresh(), $product, $item];
    }

    private function physicalStock(Product $product): float
    {
        return (float) Stock::where('product_id', $product->id)->sum('quantity');
    }

    private function findExitMovement(SalesOrder $order): ?InventoryMovement
    {
        return InventoryMovement::where('reference_type', 'sales_order')
            ->where('reference_id', $order->id)
            ->where('movement_type', 'exit')
            ->first();
    }

    private function assertEntryBalances(JournalEntry $entry, float $expectedAmount, string $context): float
    {
        $this->assertEquals('posted', $entry->status, "$context: el asiento debe quedar posted");

        $debit = (float) $entry->journalLines->sum('debit');
        $credit = (float) $entry->journalLines->sum('credit');

        $this->assertEqualsWithDelta($debit, $credit, 0.01, "$context: el asiento debe cuadrar (debe == haber)");
        $this->assertGreaterThan(0, $debit, "$context: el importe del asiento debe ser > 0");
        $this->assertEqualsWithDelta($expectedAmount, $debit, 0.01, "$context: importe del asiento incorrecto");

        return $debit;
    }

    public function test_delivery_discounts_stock_and_posts_balanced_cogs(): void
    {
        [$order, $product, $item] = $this->runDeliveredCycle();

        // Invariante 1: el stock fisico baja EXACTAMENTE la cantidad vendida.
        $this->assertEquals(
            self::INITIAL_STOCK - self::SOLD_QTY,
            $this->physicalStock($product),
            'La entrega debe descontar del stock fisico exactamente la cantidad vendida'
        );

        // Invariante 2: salida de inventario trazable a la orden, con idempotency_key.
        $exit = $this->findExitMovement($order);
        $this->assertNotNull($exit, 'La entrega debe crear el movimiento exit con reference sales_order');
        $this->assertEquals(self::SOLD_QTY, (float) $exit->quantity);
        $this->assertEquals(self::UNIT_COST, (float) $exit->unit_cost);
        $this->assertEquals(
            "sales_order:{$order->id}:item:{$item->id}",
            $exit->idempotency_key,
            'La salida debe llevar la clave natural de idempotencia'
        );
        $this->assertEquals('completed', $exit->status);

        // Invariante 3: el asiento COGS existe, cuadra y su importe es qty * unit_cost.
        $this->assertNotNull($exit->gl_journal_entry_id, 'La salida debe postearse a GL (COGS)');
        $entry = JournalEntry::findOrFail($exit->gl_journal_entry_id);
        $this->assertEntryBalances($entry, self::EXPECTED_COGS, 'COGS');

        $cogsLine = $entry->journalLines->first(fn ($l) => (float) $l->debit > 0);
        $assetLine = $entry->journalLines->first(fn ($l) => (float) $l->credit > 0);
        $this->assertEquals('5101', Account::find($cogsLine->account_id)?->code, 'El cargo del COGS va a 5101 (Costo de Ventas)');
        $this->assertEquals('1108', Account::find($assetLine->account_id)?->code, 'El abono del COGS va a 1108 (Almacen)');
    }

    public function test_ar_invoice_is_auto_created_posted_and_settled_by_real_payment_flow(): void
    {
        [$order] = $this->runDeliveredCycle();

        // Invariante 4: la ARInvoice se creo SOLA (listener del evento de entrega),
        // sin llamar a facturar a mano.
        $invoices = ARInvoice::where('sales_order_id', $order->id)->get();
        $this->assertCount(1, $invoices, 'Entregar la orden debe crear UNA ARInvoice automaticamente');

        $invoice = $invoices->first();
        $this->assertEquals('posted', $invoice->status);
        $this->assertEqualsWithDelta(
            (float) $order->total_amount,
            (float) $invoice->total_amount,
            0.01,
            'El total de la factura debe cuadrar con el total de la orden'
        );

        $order->refresh();
        $this->assertEquals($invoice->id, $order->ar_invoice_id);
        $this->assertEquals('invoiced', $order->invoicing_status);

        // Invariante 5: el asiento de la factura (DR 1104 Clientes / CR 4101 Ventas) cuadra.
        $this->assertNotNull($invoice->journal_entry_id, 'La factura debe tener GL posting');
        $entry = JournalEntry::findOrFail($invoice->journal_entry_id);
        $this->assertEntryBalances($entry, self::ORDER_TOTAL, 'Factura AR');

        $arLine = $entry->journalLines->first(fn ($l) => (float) $l->debit > 0);
        $revenueLine = $entry->journalLines->first(fn ($l) => (float) $l->credit > 0);
        $this->assertEquals('1104', Account::find($arLine->account_id)?->code, 'El cargo de la factura va a 1104 (Clientes)');
        $this->assertEquals('4101', Account::find($revenueLine->account_id)?->code, 'El abono de la factura va a 4101 (Ventas)');

        // Invariante 6: cobro por el flujo REAL de pagos AR
        // (POST /ar-invoices/{id}/register-payment -> ARInvoicePaymentRegistrationService
        // -> PaymentApplicationService). Un pago por el total liquida la factura.
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/register-payment", [
                'payment_date' => now()->format('Y-m-d'),
                'amount' => self::ORDER_TOTAL,
                'forma_pago' => '03',
            ]);

        $invoice->refresh();
        $this->assertEqualsWithDelta(self::ORDER_TOTAL, (float) $invoice->paid_amount, 0.01, 'El pago debe quedar aplicado en la factura');
        $this->assertEqualsWithDelta(0.0, (float) $invoice->total_amount - (float) $invoice->paid_amount, 0.01, 'El balance de la factura debe quedar en 0');
        $this->assertEquals('paid', $invoice->status, 'La factura debe quedar liquidada (paid)');

        $this->assertDatabaseHas('payment_applications', [
            'ar_invoice_id' => $invoice->id,
            'is_active' => true,
        ]);
    }

    public function test_retries_do_not_duplicate_exits_nor_invoices(): void
    {
        [$order] = $this->runDeliveredCycle();

        $exitCount = fn () => InventoryMovement::where('reference_type', 'sales_order')
            ->where('reference_id', $order->id)
            ->where('movement_type', 'exit')
            ->count();
        $invoiceCount = fn () => ARInvoice::where('sales_order_id', $order->id)->count();

        $this->assertEquals(1, $exitCount());
        $this->assertEquals(1, $invoiceCount());

        // Reintento 1: re-entrar a 'delivered' lo bloquea la maquina de estados.
        try {
            app(OrderStatusService::class)->updateStatus($order->fresh(), 'delivered');
            $this->fail('La maquina de estados debe impedir re-entrar a delivered');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Cannot transition', $e->getMessage());
        }
        $this->assertEquals(1, $exitCount(), 'El reintento de entrega no debe duplicar salidas');
        $this->assertEquals(1, $invoiceCount(), 'El reintento de entrega no debe duplicar facturas');

        // Reintento 2: avanzar a 'completed' (transicion valida) no re-ejecuta efectos.
        $order = app(OrderStatusService::class)->updateStatus($order->fresh(), 'completed');
        $this->assertEquals(1, $exitCount(), 'Completar la orden no debe duplicar salidas');
        $this->assertEquals(1, $invoiceCount(), 'Completar la orden no debe duplicar facturas');

        // Reintento 3: el mecanismo REAL de replay de eventos de produccion
        // (EventReplayService) sobre una orden completed ya facturada no duplica nada.
        app(EventReplayService::class)->replayOrderToCash();
        $this->assertEquals(1, $exitCount(), 'El replay de eventos no debe duplicar salidas');
        $this->assertEquals(1, $invoiceCount(), 'El replay de eventos no debe duplicar facturas');
    }
}
