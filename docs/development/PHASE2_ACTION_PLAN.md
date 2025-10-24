# PHASE 2 - FINANCE MODULE REGENERATION & INTEGRATION

**Objetivo:** Regenerar el módulo Finance con integración completa al módulo Accounting (GL posting automático)

**Status:** NOT STARTED

**Última actualización:** 2025-10-24

---

## REGLA CRÍTICA

**NO AVANZAR A PHASE 3 HASTA QUE PHASE 1 Y PHASE 2 TENGAN 100% DE TESTS PASSED**

---

## Contexto

El módulo Finance actual fue creado antes del módulo Accounting. Necesitamos:

1. **Regenerar Finance** con integración a Accounting
2. **Implementar GL Posting** automático para todas las transacciones
3. **Crear Services** para lógica de negocio compleja
4. **Tests de integración** Finance ↔ Accounting

---

## Arquitectura Target

### Entidades Finance (Actuales)
1. **ARInvoice** (Accounts Receivable - Cuentas por Cobrar)
2. **APInvoice** (Accounts Payable - Cuentas por Pagar)
3. **Payment** (Pagos - aplicados a AR)
4. **Receipt** (Recibos - aplicados a AP)
5. **BankAccount** (Cuentas bancarias)
6. **PaymentMethod** (Métodos de pago)

### Nuevas Entidades Requeridas
7. **PaymentApplication** (Aplicación de pagos a facturas)
8. **PaymentTerm** (Términos de pago: Net 30, etc.)
9. **CreditNote** (Notas de crédito)
10. **DebitNote** (Notas de débito)

### Integración con Accounting

Cada transacción financiera debe crear automáticamente:
- **JournalEntry** (asiento contable)
- **JournalLine** (líneas del asiento - debe balancear)
- **AccountBalance** updates (actualización de saldos)

---

## FASE 2A: Análisis y Diseño (2 horas)

### Tarea 1: Analizar módulo Finance actual

**Checklist:**
- [ ] Listar todas las entidades actuales
- [ ] Revisar migraciones existentes
- [ ] Identificar campos que necesitan actualización
- [ ] Documentar relaciones actuales
- [ ] Identificar qué se puede reutilizar

**Comando:**
```bash
# Listar entidades
ls -la Modules/Finance/app/Models/

# Ver migraciones
ls -la Modules/Finance/Database/migrations/

# Contar tests actuales
find Modules/Finance/tests -name "*Test.php" | wc -l
```

**Output esperado:** Documento con inventory completo del módulo actual.

### Tarea 2: Diseñar integración GL Posting

**Decisiones de diseño:**

1. **Cuentas GL requeridas** (deben existir en catálogo):
   ```
   - 1020.001 - Banco (Asset)
   - 1100.001 - Clientes (Asset)
   - 2100.001 - Proveedores (Liability)
   - 4100.001 - Ingresos por ventas (Revenue)
   - 5100.001 - Gastos (Expense)
   - 1030.001 - IVA por cobrar (Asset)
   - 2110.001 - IVA por pagar (Liability)
   ```

2. **Asientos contables automáticos:**

   **AR Invoice (Factura de Cliente):**
   ```
   DR 1100.001 Clientes          $1,000
       CR 4100.001 Ingresos              $1,000
   ```

   **AR Payment (Pago de Cliente):**
   ```
   DR 1020.001 Banco             $1,000
       CR 1100.001 Clientes              $1,000
   ```

   **AP Invoice (Factura de Proveedor):**
   ```
   DR 5100.001 Gastos            $1,000
       CR 2100.001 Proveedores           $1,000
   ```

   **AP Payment (Pago a Proveedor):**
   ```
   DR 2100.001 Proveedores       $1,000
       CR 1020.001 Banco                 $1,000
   ```

3. **Services requeridos:**
   - `ARInvoiceService` - Crear factura + GL entry
   - `APInvoiceService` - Crear factura + GL entry
   - `PaymentApplicationService` - Aplicar pago + GL entry
   - `ReceiptApplicationService` - Aplicar recibo + GL entry

**Checklist:**
- [ ] Documentar todos los asientos contables
- [ ] Definir estructura de AccountMapping (entity_type → account_id)
- [ ] Diseñar flujo de validaciones (balance check, account validation)
- [ ] Definir manejo de errores (rollback strategy)

---

## FASE 2B: Configuración JSON del Módulo (2 horas)

### Tarea 3: Crear configuración JSON para regeneración

**Archivo:** `/tmp/finance_module_config.json`

```json
{
  "entities": {
    "ARInvoice": {
      "name": "ARInvoice",
      "tableName": "ar_invoices",
      "fields": [
        {"name": "invoice_number", "type": "string", "required": true, "fillable": true, "sortable": true, "filterable": true, "unique": true},
        {"name": "invoice_date", "type": "date", "required": true, "fillable": true, "sortable": true, "filterable": true},
        {"name": "due_date", "type": "date", "required": true, "fillable": true, "sortable": true, "filterable": true},
        {"name": "customer_id", "type": "integer", "required": true, "fillable": true, "filterable": true},
        {"name": "currency", "type": "string", "required": true, "fillable": true, "default": "MXN", "filterable": true},
        {"name": "subtotal", "type": "decimal", "required": true, "fillable": true, "sortable": true},
        {"name": "tax_amount", "type": "decimal", "required": true, "fillable": true},
        {"name": "total_amount", "type": "decimal", "required": true, "fillable": true, "sortable": true},
        {"name": "paid_amount", "type": "decimal", "required": false, "fillable": true, "default": 0},
        {"name": "status", "type": "string", "required": true, "fillable": true, "default": "draft", "filterable": true},
        {"name": "journal_entry_id", "type": "integer", "required": false, "fillable": true},
        {"name": "notes", "type": "text", "required": false, "fillable": true},
        {"name": "metadata", "type": "json", "required": false, "fillable": true},
        {"name": "is_active", "type": "boolean", "required": false, "fillable": true, "default": true, "filterable": true}
      ]
    },
    "APInvoice": {
      "name": "APInvoice",
      "tableName": "ap_invoices",
      "fields": [
        {"name": "invoice_number", "type": "string", "required": true, "fillable": true, "sortable": true, "filterable": true, "unique": true},
        {"name": "invoice_date", "type": "date", "required": true, "fillable": true, "sortable": true, "filterable": true},
        {"name": "due_date", "type": "date", "required": true, "fillable": true, "sortable": true, "filterable": true},
        {"name": "supplier_id", "type": "integer", "required": true, "fillable": true, "filterable": true},
        {"name": "currency", "type": "string", "required": true, "fillable": true, "default": "MXN", "filterable": true},
        {"name": "subtotal", "type": "decimal", "required": true, "fillable": true, "sortable": true},
        {"name": "tax_amount", "type": "decimal", "required": true, "fillable": true},
        {"name": "total_amount", "type": "decimal", "required": true, "fillable": true, "sortable": true},
        {"name": "paid_amount", "type": "decimal", "required": false, "fillable": true, "default": 0},
        {"name": "status", "type": "string", "required": true, "fillable": true, "default": "draft", "filterable": true},
        {"name": "journal_entry_id", "type": "integer", "required": false, "fillable": true},
        {"name": "notes", "type": "text", "required": false, "fillable": true},
        {"name": "metadata", "type": "json", "required": false, "fillable": true},
        {"name": "is_active", "type": "boolean", "required": false, "fillable": true, "default": true, "filterable": true}
      ]
    },
    "Payment": {
      "name": "Payment",
      "tableName": "payments",
      "fields": [
        {"name": "payment_number", "type": "string", "required": true, "fillable": true, "sortable": true, "filterable": true, "unique": true},
        {"name": "payment_date", "type": "date", "required": true, "fillable": true, "sortable": true, "filterable": true},
        {"name": "customer_id", "type": "integer", "required": true, "fillable": true, "filterable": true},
        {"name": "bank_account_id", "type": "integer", "required": true, "fillable": true, "filterable": true},
        {"name": "payment_method_id", "type": "integer", "required": true, "fillable": true, "filterable": true},
        {"name": "amount", "type": "decimal", "required": true, "fillable": true, "sortable": true},
        {"name": "currency", "type": "string", "required": true, "fillable": true, "default": "MXN"},
        {"name": "applied_amount", "type": "decimal", "required": false, "fillable": true, "default": 0},
        {"name": "unapplied_amount", "type": "decimal", "required": false, "fillable": true, "default": 0},
        {"name": "status", "type": "string", "required": true, "fillable": true, "default": "unapplied", "filterable": true},
        {"name": "journal_entry_id", "type": "integer", "required": false, "fillable": true},
        {"name": "reference", "type": "string", "required": false, "fillable": true},
        {"name": "notes", "type": "text", "required": false, "fillable": true},
        {"name": "metadata", "type": "json", "required": false, "fillable": true},
        {"name": "is_active", "type": "boolean", "required": false, "fillable": true, "default": true, "filterable": true}
      ]
    },
    "PaymentApplication": {
      "name": "PaymentApplication",
      "tableName": "payment_applications",
      "fields": [
        {"name": "payment_id", "type": "integer", "required": true, "fillable": true, "filterable": true},
        {"name": "ar_invoice_id", "type": "integer", "required": true, "fillable": true, "filterable": true},
        {"name": "amount", "type": "decimal", "required": true, "fillable": true},
        {"name": "application_date", "type": "date", "required": true, "fillable": true, "sortable": true, "filterable": true},
        {"name": "notes", "type": "text", "required": false, "fillable": true},
        {"name": "is_active", "type": "boolean", "required": false, "fillable": true, "default": true}
      ]
    },
    "BankAccount": {
      "name": "BankAccount",
      "tableName": "bank_accounts",
      "fields": [
        {"name": "account_number", "type": "string", "required": true, "fillable": true, "sortable": true, "unique": true},
        {"name": "account_name", "type": "string", "required": true, "fillable": true, "sortable": true, "filterable": true},
        {"name": "bank_name", "type": "string", "required": true, "fillable": true, "filterable": true},
        {"name": "currency", "type": "string", "required": true, "fillable": true, "default": "MXN", "filterable": true},
        {"name": "gl_account_id", "type": "integer", "required": true, "fillable": true, "filterable": true},
        {"name": "current_balance", "type": "decimal", "required": false, "fillable": true, "default": 0},
        {"name": "opening_balance", "type": "decimal", "required": false, "fillable": true, "default": 0},
        {"name": "status", "type": "string", "required": true, "fillable": true, "default": "active", "filterable": true},
        {"name": "metadata", "type": "json", "required": false, "fillable": true},
        {"name": "is_active", "type": "boolean", "required": false, "fillable": true, "default": true, "filterable": true}
      ]
    },
    "PaymentMethod": {
      "name": "PaymentMethod",
      "tableName": "payment_methods",
      "fields": [
        {"name": "code", "type": "string", "required": true, "fillable": true, "sortable": true, "unique": true},
        {"name": "name", "type": "string", "required": true, "fillable": true, "sortable": true, "filterable": true},
        {"name": "type", "type": "string", "required": true, "fillable": true, "filterable": true},
        {"name": "requires_reference", "type": "boolean", "required": false, "fillable": true, "default": false},
        {"name": "is_active", "type": "boolean", "required": false, "fillable": true, "default": true, "filterable": true}
      ]
    }
  },
  "relationships": [
    {"from": "ARInvoice", "to": "Customer", "type": "belongsTo", "foreignKey": "customer_id"},
    {"from": "ARInvoice", "to": "JournalEntry", "type": "belongsTo", "foreignKey": "journal_entry_id"},
    {"from": "ARInvoice", "to": "PaymentApplication", "type": "hasMany"},

    {"from": "APInvoice", "to": "Supplier", "type": "belongsTo", "foreignKey": "supplier_id"},
    {"from": "APInvoice", "to": "JournalEntry", "type": "belongsTo", "foreignKey": "journal_entry_id"},

    {"from": "Payment", "to": "Customer", "type": "belongsTo", "foreignKey": "customer_id"},
    {"from": "Payment", "to": "BankAccount", "type": "belongsTo", "foreignKey": "bank_account_id"},
    {"from": "Payment", "to": "PaymentMethod", "type": "belongsTo", "foreignKey": "payment_method_id"},
    {"from": "Payment", "to": "JournalEntry", "type": "belongsTo", "foreignKey": "journal_entry_id"},
    {"from": "Payment", "to": "PaymentApplication", "type": "hasMany"},

    {"from": "PaymentApplication", "to": "Payment", "type": "belongsTo", "foreignKey": "payment_id"},
    {"from": "PaymentApplication", "to": "ARInvoice", "type": "belongsTo", "foreignKey": "ar_invoice_id"},

    {"from": "BankAccount", "to": "Account", "type": "belongsTo", "foreignKey": "gl_account_id"}
  ]
}
```

**Checklist:**
- [ ] Crear archivo JSON completo
- [ ] Validar sintaxis JSON
- [ ] Verificar que todas las relaciones cross-module están correctas
- [ ] Documentar campos calculados (paidAmount, remainingBalance)

---

## FASE 2C: Regeneración del Módulo (3 horas)

### Tarea 4: Backup del módulo Finance actual

```bash
# Crear backup
cp -r Modules/Finance Modules/Finance.backup.$(date +%Y%m%d_%H%M%S)

# Verificar backup
ls -la Modules/Finance.backup.*
```

**Checklist:**
- [ ] Backup creado exitosamente
- [ ] Verificar que el backup contiene todos los archivos

### Tarea 5: Eliminar módulo Finance actual

```bash
# Usar comando de force delete
php artisan module:force-delete Finance
```

**Checklist:**
- [ ] Módulo eliminado de `Modules/`
- [ ] Registros removidos de `app/JsonApi/V1/Server.php`
- [ ] Seeder removido de `DatabaseSeeder.php`
- [ ] Verificar con `php artisan module:list`

### Tarea 6: Regenerar módulo con nueva configuración

```bash
# Regenerar con config JSON
php artisan module:advanced-blueprint Finance --config="/tmp/finance_module_config.json"
```

**Checklist:**
- [ ] Módulo generado sin errores
- [ ] Verificar estructura de directorios
- [ ] Verificar que las migraciones tienen las relaciones correctas
- [ ] Verificar que los schemas importan modelos de otros módulos

**Verificación:**
```bash
# Listar archivos generados
find Modules/Finance -type f -name "*.php" | wc -l

# Ver schemas generados
ls -la Modules/Finance/app/JsonApi/V1/*/
```

### Tarea 7: Ejecutar migraciones

```bash
# Fresh migration con seed
php artisan migrate:fresh --seed
```

**Checklist:**
- [ ] Migraciones ejecutadas sin errores
- [ ] Seeders ejecutados correctamente
- [ ] Verificar datos en BD

**Verificación:**
```bash
# Ver tablas creadas
php artisan db:table ar_invoices
php artisan db:table ap_invoices
php artisan db:table payments
```

---

## FASE 2D: Implementación de Services (8 horas)

### Tarea 8: Crear ARInvoiceService

**Archivo:** `Modules/Finance/app/Services/ARInvoiceService.php`

**Responsabilidades:**
1. Crear AR Invoice con validaciones de negocio
2. Generar invoice_number secuencial
3. Crear JournalEntry automático
4. Validar que las cuentas GL existan
5. Actualizar AccountBalance

**Código base:**
```php
<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\ARInvoice;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class ARInvoiceService
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    /**
     * Crear AR Invoice con GL posting automático
     */
    public function createInvoice(array $data): ARInvoice
    {
        return DB::transaction(function () use ($data) {
            // 1. Validar cuentas GL
            $customerAccount = Account::where('code', '1100.001')->firstOrFail(); // Clientes
            $revenueAccount = Account::where('code', '4100.001')->firstOrFail(); // Ingresos

            // 2. Generar invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // 3. Crear invoice
            $invoice = ARInvoice::create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoiceDate'],
                'due_date' => $data['dueDate'],
                'customer_id' => $data['customerId'],
                'currency' => $data['currency'] ?? 'MXN',
                'subtotal' => $data['subtotal'],
                'tax_amount' => $data['taxAmount'],
                'total_amount' => $data['totalAmount'],
                'paid_amount' => 0,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            // 4. Crear JournalEntry
            $journalEntry = $this->accountingService->createJournalEntry(
                journalCode: 'AR',
                entryDate: $data['invoiceDate'],
                description: "AR Invoice #{$invoiceNumber}",
                reference: $invoiceNumber,
                lines: [
                    [
                        'account_id' => $customerAccount->id,
                        'debit_amount' => $data['totalAmount'],
                        'credit_amount' => 0,
                        'description' => "Cliente - Invoice #{$invoiceNumber}",
                    ],
                    [
                        'account_id' => $revenueAccount->id,
                        'debit_amount' => 0,
                        'credit_amount' => $data['totalAmount'],
                        'description' => "Ingresos - Invoice #{$invoiceNumber}",
                    ],
                ]
            );

            // 5. Vincular journal entry
            $invoice->update(['journal_entry_id' => $journalEntry->id]);

            return $invoice->fresh(['journalEntry', 'customer']);
        });
    }

    /**
     * Generar invoice number secuencial
     */
    private function generateInvoiceNumber(): string
    {
        $lastInvoice = ARInvoice::orderBy('id', 'desc')->first();
        $nextNumber = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, 3)) + 1 : 1;
        return 'AR-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular remaining balance
     */
    public function calculateRemainingBalance(ARInvoice $invoice): float
    {
        return $invoice->total_amount - $invoice->paid_amount;
    }
}
```

**Checklist:**
- [ ] Crear archivo ARInvoiceService.php
- [ ] Implementar createInvoice() con GL posting
- [ ] Implementar generateInvoiceNumber()
- [ ] Implementar calculateRemainingBalance()
- [ ] Agregar validaciones de negocio
- [ ] Escribir tests unitarios para el service

### Tarea 9: Crear PaymentApplicationService

**Archivo:** `Modules/Finance/app/Services/PaymentApplicationService.php`

**Responsabilidades:**
1. Aplicar payment a AR invoice
2. Actualizar paid_amount en invoice
3. Crear JournalEntry para el payment
4. Validar que payment amount no exceda invoice balance

**Código base:**
```php
<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Models\ARInvoice;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class PaymentApplicationService
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    /**
     * Aplicar payment a AR invoice
     */
    public function applyPayment(Payment $payment, ARInvoice $invoice, float $amount): PaymentApplication
    {
        return DB::transaction(function () use ($payment, $invoice, $amount) {
            // 1. Validaciones
            $remainingBalance = $invoice->total_amount - $invoice->paid_amount;
            if ($amount > $remainingBalance) {
                throw new \Exception("Payment amount exceeds invoice balance");
            }

            $unappliedAmount = $payment->amount - $payment->applied_amount;
            if ($amount > $unappliedAmount) {
                throw new \Exception("Payment amount exceeds unapplied payment balance");
            }

            // 2. Crear PaymentApplication
            $application = PaymentApplication::create([
                'payment_id' => $payment->id,
                'ar_invoice_id' => $invoice->id,
                'amount' => $amount,
                'application_date' => now(),
            ]);

            // 3. Actualizar invoice paid_amount
            $invoice->increment('paid_amount', $amount);
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->update(['status' => 'paid']);
            }

            // 4. Actualizar payment applied_amount
            $payment->increment('applied_amount', $amount);
            $payment->update([
                'unapplied_amount' => $payment->amount - $payment->applied_amount,
                'status' => $payment->applied_amount >= $payment->amount ? 'applied' : 'partial',
            ]);

            return $application->fresh(['payment', 'arInvoice']);
        });
    }
}
```

**Checklist:**
- [ ] Crear archivo PaymentApplicationService.php
- [ ] Implementar applyPayment() con validaciones
- [ ] Implementar unapplyPayment() (reversal)
- [ ] Agregar validaciones de negocio
- [ ] Escribir tests unitarios para el service

### Tarea 10: Actualizar Controllers para usar Services

**Para cada controller:**
- [ ] ARInvoiceController → usar ARInvoiceService
- [ ] PaymentController → usar PaymentApplicationService
- [ ] Agregar validaciones antes de delegar al service
- [ ] Manejar excepciones del service

**Ejemplo:**
```php
// En ARInvoiceController.php
public function store(ARInvoiceRequest $request): JsonApiResponse
{
    try {
        $invoice = $this->arInvoiceService->createInvoice($request->validated());

        return ARInvoiceResource::make($invoice)
            ->response()
            ->setStatusCode(201);
    } catch (\Exception $e) {
        return response()->json([
            'errors' => [
                [
                    'status' => '422',
                    'title' => 'Invoice creation failed',
                    'detail' => $e->getMessage(),
                ],
            ],
        ], 422);
    }
}
```

---

## FASE 2E: Tests de Integración (6 horas)

### Tarea 11: Crear tests de integración Finance ↔ Accounting

**Archivo:** `Modules/Finance/tests/Integration/ARInvoiceAccountingIntegrationTest.php`

```php
<?php

namespace Modules\Finance\Tests\Integration;

use Tests\TestCase;
use Modules\Finance\Models\ARInvoice;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Account;
use Modules\Sales\Models\Customer;

class ARInvoiceAccountingIntegrationTest extends TestCase
{
    public function test_creating_ar_invoice_creates_journal_entry(): void
    {
        $customer = Customer::factory()->create();

        $data = [
            'type' => 'ar-invoices',
            'attributes' => [
                'invoiceDate' => '2025-01-15',
                'dueDate' => '2025-02-15',
                'currency' => 'MXN',
                'subtotal' => 1000.00,
                'taxAmount' => 160.00,
                'totalAmount' => 1160.00,
            ],
            'relationships' => [
                'customer' => [
                    'data' => ['type' => 'customers', 'id' => (string) $customer->id],
                ],
            ],
        ];

        $response = $this
            ->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->includePaths('journalEntry')
            ->withData($data)
            ->post('/api/v1/ar-invoices');

        $response->assertCreated();

        // Verificar que se creó el JournalEntry
        $invoice = ARInvoice::latest()->first();
        $this->assertNotNull($invoice->journal_entry_id);

        // Verificar que el entry balancea
        $journalEntry = $invoice->journalEntry;
        $totalDebit = $journalEntry->lines->sum('debit_amount');
        $totalCredit = $journalEntry->lines->sum('credit_amount');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(1160.00, $totalDebit);
    }

    public function test_applying_payment_updates_account_balances(): void
    {
        // Similar test para payment application
    }
}
```

**Checklist:**
- [ ] Test: AR Invoice creation → GL posting
- [ ] Test: Payment application → GL posting
- [ ] Test: Balance validation
- [ ] Test: Account balance updates
- [ ] Test: Rollback on error
- [ ] Test: Multiple payments on single invoice
- [ ] Test: Overpayment prevention

### Tarea 12: Ejecutar todos los tests del módulo Finance

```bash
# Ejecutar tests de Finance
php artisan test Modules/Finance/
```

**Criterio de aceptación:**
- ✅ 100% tests passing
- ✅ Cobertura > 80%
- ✅ Integration tests passing

---

## FASE 2F: Seeders y Datos de Prueba (2 horas)

### Tarea 13: Crear seeder con cuentas GL requeridas

**Archivo:** `Modules/Finance/Database/seeders/GLAccountsSeeder.php`

```php
<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountType;

class GLAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $assetType = AccountType::where('code', 'ASSET')->first();
        $liabilityType = AccountType::where('code', 'LIABILITY')->first();
        $revenueType = AccountType::where('code', 'REVENUE')->first();
        $expenseType = AccountType::where('code', 'EXPENSE')->first();

        // Crear cuentas requeridas por Finance
        Account::firstOrCreate(['code' => '1020.001'], [
            'name' => 'Banco',
            'account_type_id' => $assetType->id,
            'nature' => 'debit',
            'level' => 3,
            'currency' => 'MXN',
            'is_postable' => true,
            'is_cash_flow' => true,
            'status' => 'active',
        ]);

        Account::firstOrCreate(['code' => '1100.001'], [
            'name' => 'Clientes',
            'account_type_id' => $assetType->id,
            'nature' => 'debit',
            'level' => 3,
            'currency' => 'MXN',
            'is_postable' => true,
            'is_cash_flow' => false,
            'status' => 'active',
        ]);

        // ... más cuentas
    }
}
```

**Checklist:**
- [ ] Crear GLAccountsSeeder.php
- [ ] Seed todas las cuentas requeridas
- [ ] Crear PaymentMethodsSeeder.php
- [ ] Registrar seeders en FinanceDatabaseSeeder

---

## FASE 2G: Documentación (2 horas)

### Tarea 14: Crear documentación de Phase 2

**Archivo:** `docs/development/FINANCE_ACCOUNTING_PHASE2_REPORT.md`

**Contenido:**
- [ ] Arquitectura del módulo Finance regenerado
- [ ] Diagrama de GL posting flow
- [ ] Lista de servicios y responsabilidades
- [ ] Guía de integración con Accounting
- [ ] Endpoints API documentados
- [ ] Ejemplos de requests/responses

---

## VERIFICACIÓN FINAL - PHASE 2

```bash
# 1. Ejecutar TODOS los tests
php artisan test

# 2. Verificar específicamente Finance
php artisan test Modules/Finance/

# 3. Verificar integration tests
php artisan test Modules/Finance/tests/Integration/
```

**Criterios de aceptación:**
- ✅ Módulo Finance regenerado exitosamente
- ✅ 100% tests Finance passing
- ✅ GL posting funcional en ARInvoice
- ✅ GL posting funcional en Payment
- ✅ PaymentApplication funcional
- ✅ Services implementados y testeados
- ✅ Integration tests passing
- ✅ Documentación completa
- ✅ **CRITICAL:** 0 test failures en Phase 2

---

## SIGUIENTE PASO: Regresar a Phase 1

Una vez completado Phase 2 con 100% tests passing:

1. Regresar a `docs/development/PHASE1_PENDING_FIXES.md`
2. Fix todos los 154 failures de Accounting
3. Verificar que Phase 1 + Phase 2 = 100% tests passing
4. Entonces avanzar a Phase 3

---

**Documento creado:** 2025-10-24
**Última actualización:** 2025-10-24
**Status:** NOT STARTED
**Tiempo estimado:** 25-30 horas
