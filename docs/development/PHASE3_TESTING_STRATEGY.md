# 🧪 FASE 3: Testing Strategy - Business Rules & Cross-Module Integration

**Objetivo:** Asegurar que los flujos de trabajo cross-module funcionen correctamente mediante una estrategia de testing multi-capa.

---

## 🎯 ESTRATEGIA MULTI-CAPA

### **NIVEL 1: Unit Tests - Componentes Individuales** ⚡

**Qué prueba:** Cada servicio/listener aisladamente
**Velocidad:** Muy rápida (milisegundos)
**Coverage:** Lógica de negocio

**Ejemplo:**
```php
class CreditManagementServiceTest extends TestCase
{
    public function test_validates_credit_limit_correctly()
    {
        $contact = Contact::factory()->create([
            'credit_limit' => 10000,
            'is_customer' => true
        ]);

        // Crear AR balance existente de $8000
        ARInvoice::factory()->create([
            'contact_id' => $contact->id,
            'total_amount' => 8000,
            'status' => 'posted'
        ]);

        $service = app(CreditManagementService::class);

        // ✅ Debería permitir $1500 (total $9500 < $10000)
        $this->assertTrue($service->validateCustomerCredit($contact, 1500));

        // ❌ Debería rechazar $3000 (total $11000 > $10000)
        $this->expectException(CreditLimitExceededException::class);
        $service->validateCustomerCredit($contact, 3000);
    }
}
```

---

### **NIVEL 2: Integration Tests - Flujo Completo End-to-End** 🔄

**Qué prueba:** Todo el flujo desde trigger hasta resultado final
**Velocidad:** Media (segundos)
**Coverage:** Integración completa

**Ejemplo - Order-to-Cash Completo:**
```php
class OrderToCashFlowTest extends TestCase
{
    public function test_complete_sales_order_to_cash_flow()
    {
        // ARRANGE: Setup inicial
        $customer = Contact::factory()->customer()->create(['credit_limit' => 20000]);
        $product = Product::factory()->create(['unit_price' => 100]);

        // ACT 1: Crear Sales Order
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'pending',
            'total_amount' => 1000
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 100
        ]);

        // ACT 2: Completar Sales Order (trigger event)
        event(new SalesOrderCompleted($salesOrder));

        // ASSERT 1: AR Invoice fue creada
        $this->assertDatabaseHas('ar_invoices', [
            'sales_order_id' => $salesOrder->id,
            'contact_id' => $customer->id,
            'total_amount' => 1000
        ]);

        // ASSERT 2: Sales Order sincronizado
        $salesOrder->refresh();
        $this->assertNotNull($salesOrder->ar_invoice_id);
        $this->assertEquals('complete', $salesOrder->invoicing_status);

        // ACT 3: Post AR Invoice (trigger GL)
        $arInvoice = ARInvoice::where('sales_order_id', $salesOrder->id)->first();
        $arInvoiceService = app(ARInvoiceService::class);
        $arInvoiceService->postInvoice($arInvoice);

        // ASSERT 3: Journal Entry fue creado
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => ARInvoice::class,
            'source_id' => $arInvoice->id,
            'status' => 'posted'
        ]);

        // ASSERT 4: Journal Lines balanceados
        $journalEntry = JournalEntry::where('source_id', $arInvoice->id)
            ->where('source_type', ARInvoice::class)
            ->first();

        $totalDebit = $journalEntry->journalLines->sum('debit');
        $totalCredit = $journalEntry->journalLines->sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(1000, $totalDebit);

        // ASSERT 5: GL Accounts correctos
        $debitLine = $journalEntry->journalLines->where('debit', '>', 0)->first();
        $creditLine = $journalEntry->journalLines->where('credit', '>', 0)->first();

        // Customers (Debit)
        $customersAccount = Account::where('code', '1102')->first();
        $this->assertEquals($customersAccount->id, $debitLine->account_id);

        // Revenue (Credit)
        $revenueAccount = Account::where('code', '4101')->first();
        $this->assertEquals($revenueAccount->id, $creditLine->account_id);

        // ACT 4: Aplicar Payment
        $payment = Payment::factory()->create([
            'contact_id' => $customer->id,
            'amount' => 1000,
            'status' => 'unapplied'
        ]);

        $paymentService = app(PaymentApplicationService::class);
        $paymentService->applyPayment($payment, $arInvoice, 1000);

        // ASSERT 6: Payment aplicado
        $this->assertDatabaseHas('payment_applications', [
            'payment_id' => $payment->id,
            'ar_invoice_id' => $arInvoice->id,
            'amount' => 1000
        ]);

        // ASSERT 7: AR Invoice pagada
        $arInvoice->refresh();
        $this->assertEquals(1000, $arInvoice->paid_amount);
        $this->assertEquals(0, $arInvoice->remaining_balance);
        $this->assertEquals('paid', $arInvoice->status);

        // ASSERT 8: Payment GL Entry creado
        $payment->refresh();
        $this->assertNotNull($payment->journal_entry_id);

        $paymentJE = JournalEntry::find($payment->journal_entry_id);
        $this->assertEquals('posted', $paymentJE->status);

        // ASSERT FINAL: Todo el flujo está completo
        $this->assertEquals('invoiced', $salesOrder->fresh()->financial_status);
        $this->assertEquals('paid', $arInvoice->fresh()->status);
    }
}
```

---

### **NIVEL 3: State Assertions - Verificación de Estado** ✅

**Qué prueba:** Estado de la base de datos después de cada acción
**Velocidad:** Rápida
**Coverage:** Sincronización de datos

**Helper Methods:**
```php
private function assertSalesOrderState(
    SalesOrder $order,
    string $expectedInvoicingStatus,
    string $expectedFinancialStatus,
    bool $shouldHaveInvoice
) {
    $order->refresh();

    $this->assertEquals($expectedInvoicingStatus, $order->invoicing_status);
    $this->assertEquals($expectedFinancialStatus, $order->financial_status);

    if ($shouldHaveInvoice) {
        $this->assertNotNull($order->ar_invoice_id);
        $this->assertDatabaseHas('ar_invoices', [
            'id' => $order->ar_invoice_id,
            'sales_order_id' => $order->id
        ]);
    }
}

// Uso en test
public function test_sales_order_states_transition_correctly()
{
    $order = SalesOrder::factory()->create(['status' => 'pending']);

    // Estado inicial
    $this->assertSalesOrderState($order, 'pending', 'not_invoiced', false);

    // Después de completar
    event(new SalesOrderCompleted($order));
    $this->assertSalesOrderState($order, 'complete', 'not_invoiced', true);

    // Después de post invoice
    $invoice = ARInvoice::where('sales_order_id', $order->id)->first();
    app(ARInvoiceService::class)->postInvoice($invoice);
    $this->assertSalesOrderState($order, 'complete', 'invoiced', true);
}
```

---

### **NIVEL 4: Observability - Logs & Debugging** 🔍

**Qué prueba:** Que podamos ver QUÉ está pasando en tiempo real
**Velocidad:** N/A (debugging tool)
**Coverage:** Troubleshooting

**Implementación en Listeners:**
```php
class SalesOrderCompletedListener
{
    public function handle(SalesOrderCompleted $event): void
    {
        Log::info('SalesOrderCompletedListener: Starting', [
            'sales_order_id' => $event->salesOrder->id,
            'total_amount' => $event->salesOrder->total_amount
        ]);

        try {
            $arInvoice = $this->createARInvoice($event->salesOrder);

            Log::info('SalesOrderCompletedListener: AR Invoice created', [
                'ar_invoice_id' => $arInvoice->id
            ]);

            $this->syncSalesOrder($event->salesOrder, $arInvoice);

            Log::info('SalesOrderCompletedListener: Completed successfully');

        } catch (\Exception $e) {
            Log::error('SalesOrderCompletedListener: Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
```

**Durante tests:**
```bash
php artisan test --filter=test_complete_sales_order_to_cash_flow

# Output mostrará:
# [INFO] SalesOrderCompletedListener: Starting (sales_order_id: 123)
# [INFO] SalesOrderCompletedListener: AR Invoice created (ar_invoice_id: 456)
# [INFO] ARInvoicePostedListener: Starting (ar_invoice_id: 456)
# [INFO] AccountingService: Journal Entry created (journal_entry_id: 789)
```

---

### **NIVEL 5: Edge Cases Testing** 🚨

**Qué prueba:** Comportamiento en situaciones anormales
**Velocidad:** Media
**Coverage:** Robustez y manejo de errores

**Ejemplos:**
```php
class OrderToCashEdgeCasesTest extends TestCase
{
    public function test_handles_credit_limit_exceeded()
    {
        $customer = Contact::factory()->create(['credit_limit' => 5000]);

        // Ya tiene $4500 de balance
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 4500,
            'status' => 'posted'
        ]);

        // Intentar crear order de $1000 (excede límite)
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'total_amount' => 1000
        ]);

        // El evento debería lanzar excepción
        $this->expectException(CreditLimitExceededException::class);
        event(new SalesOrderCompleted($salesOrder));

        // Verificar que NO se creó AR Invoice
        $this->assertDatabaseMissing('ar_invoices', [
            'sales_order_id' => $salesOrder->id
        ]);

        // Verificar que Sales Order se marcó como blocked
        $salesOrder->refresh();
        $this->assertEquals('credit_blocked', $salesOrder->invoicing_status);
    }

    public function test_handles_closed_fiscal_period()
    {
        $closedPeriod = FiscalPeriod::factory()->create([
            'status' => 'closed',
            'year' => 2024,
            'month' => 10
        ]);

        $salesOrder = SalesOrder::factory()->create([
            'order_date' => '2024-10-15' // Fecha en período cerrado
        ]);

        event(new SalesOrderCompleted($salesOrder));

        $invoice = ARInvoice::where('sales_order_id', $salesOrder->id)->first();

        // Intentar post en período cerrado
        $this->expectException(PeriodClosedException::class);
        app(ARInvoiceService::class)->postInvoice($invoice);
    }

    public function test_handles_duplicate_event_idempotently()
    {
        $salesOrder = SalesOrder::factory()->create();

        // Disparar evento 2 veces
        event(new SalesOrderCompleted($salesOrder));
        event(new SalesOrderCompleted($salesOrder));

        // Debería haber solo 1 AR Invoice
        $invoices = ARInvoice::where('sales_order_id', $salesOrder->id)->get();
        $this->assertCount(1, $invoices);
    }
}
```

---

### **NIVEL 6: Performance Testing** ⚡

**Qué prueba:** Que los flujos sean RÁPIDOS incluso bajo carga
**Velocidad:** Lenta (por volumen)
**Coverage:** Escalabilidad

```php
class OrderToCashPerformanceTest extends TestCase
{
    public function test_handles_100_concurrent_orders()
    {
        $customer = Contact::factory()->create(['credit_limit' => 1000000]);

        $startTime = microtime(true);

        // Crear 100 órdenes
        $orders = collect();
        for ($i = 0; $i < 100; $i++) {
            $order = SalesOrder::factory()->create([
                'contact_id' => $customer->id,
                'total_amount' => 1000
            ]);
            $orders->push($order);
        }

        // Disparar eventos
        foreach ($orders as $order) {
            event(new SalesOrderCompleted($order));
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // ms

        // Verificar todas creadas
        $this->assertEquals(100, ARInvoice::count());

        // Performance assertion: < 5000ms total (50ms promedio)
        $this->assertLessThan(5000, $duration,
            "Processing took {$duration}ms, expected < 5000ms"
        );

        // Log para análisis
        Log::info("Performance: 100 orders processed in {$duration}ms");
    }
}
```

---

## 📊 RESUMEN DE ESTRATEGIA

| Nivel | Tipo | Qué Prueba | Velocidad | Coverage |
|-------|------|------------|-----------|----------|
| 1 | Unit | Servicios individuales | ⚡⚡⚡ | Lógica de negocio |
| 2 | Integration | Flujo end-to-end | ⚡⚡ | Integración completa |
| 3 | State | Base de datos en cada paso | ⚡⚡ | Sincronización |
| 4 | Observability | Logs y debugging | ⚡ | Troubleshooting |
| 5 | Edge Cases | Errores y excepciones | ⚡⚡ | Robustez |
| 6 | Performance | Carga y velocidad | ⚡ | Escalabilidad |

---

## 📋 CHECKLIST DE VALIDACIÓN COMPLETA

Para cada flujo (Order-to-Cash, Procure-to-Pay), verificar:

- [ ] ✅ **Event dispara correctamente** (Listener ejecuta)
- [ ] ✅ **Entidad destino se crea** (AR/AP Invoice existe)
- [ ] ✅ **Sincronización bidireccional** (IDs y status actualizados)
- [ ] ✅ **GL Entry se crea automáticamente** (Journal Entry + Lines)
- [ ] ✅ **GL balanceado** (Debit = Credit)
- [ ] ✅ **Cuentas GL correctas** (Customers, Revenue, etc.)
- [ ] ✅ **Audit trail registrado** (created_by, posted_by)
- [ ] ✅ **Business rules aplicadas** (Credit limit, Period lock)
- [ ] ✅ **Manejo de errores** (Exceptions, rollback)
- [ ] ✅ **Idempotencia** (Mismo evento 2x = mismo resultado)
- [ ] ✅ **Performance aceptable** (< 500ms por operación)

---

## 🚀 EJECUCIÓN DE TESTS

### **Por Nivel**
```bash
# Tests unitarios rápidos
php artisan test --testsuite=Unit --filter=Credit

# Tests de integración (más lentos)
php artisan test --testsuite=Integration --filter=OrderToCash

# Performance tests
php artisan test --testsuite=Performance

# Edge cases
php artisan test --testsuite=Feature --filter=EdgeCase
```

### **Por Módulo**
```bash
# Sales integration
php artisan test Modules/Sales/tests/Integration/

# Finance integration
php artisan test Modules/Finance/tests/Integration/

# Accounting integration
php artisan test Modules/Accounting/tests/Integration/
```

### **Completo**
```bash
# TODO en paralelo
php artisan test --parallel

# Con coverage
php artisan test --coverage
```

---

## 📈 MÉTRICAS DE ÉXITO

| Métrica | Target | Cómo Medir |
|---------|--------|------------|
| **Order-to-Cash Automation** | 95%+ | % de Sales Orders que crean AR Invoice automáticamente |
| **Procure-to-Pay Automation** | 95%+ | % de Purchase Orders que crean AP Invoice automáticamente |
| **GL Posting Accuracy** | 99.9%+ | Journal Entries balanceados / Total JEs |
| **Event Processing Time** | < 500ms | Average time SalesOrderCompleted → AR Invoice created |
| **Test Coverage** | 95%+ | Lines of code covered por tests |
| **Tests Passing** | 99%+ | Expected: 1433 → 1500+ tests, <10 failures |

---

## 🎯 OUTPUT ESPERADO

```
PASS  Tests\Unit\CreditManagementServiceTest
  ✓ validates credit limit correctly
  ✓ validates overdue correctly
  ✓ calculates payment score correctly

PASS  Tests\Integration\OrderToCashFlowTest
  ✓ complete sales order to cash flow
  ✓ handles credit limit exceeded
  ✓ handles closed fiscal period

PASS  Tests\Integration\ProcureToPayFlowTest
  ✓ complete purchase order to payment flow
  ✓ handles approval workflow

PASS  Tests\Performance\EventProcessingTest
  ✓ handles 100 concurrent orders (4523ms)

Tests:  90 passed (1200 assertions)
Duration: 125s
```

---

**Última actualización:** 2025-10-27
**Responsable:** Development Team
