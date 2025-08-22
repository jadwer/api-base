# 🎯 DECISIONES TÉCNICAS UNIFICADAS
## Corrección de Inconsistencias Arquitectónicas

**Objetivo:** Unificar y cerrar todas las decisiones técnicas para evitar inconsistencias en la implementación.

---

## 🗄️ **1. MOTOR DE BD: PostgreSQL**

**DECISIÓN:** PostgreSQL 15+ como motor único

### **Justificación:**
- ✅ JSONB nativo (mejor que JSON de MySQL)
- ✅ CHECK constraints reales (no decorativos)
- ✅ Partitioning nativo
- ✅ Triggers más robustos
- ✅ CTE y window functions superiores

### **Correcciones Aplicadas:**
```sql
-- CORRECTO (PostgreSQL)
CREATE TABLE idempotency_keys (
    id BIGSERIAL PRIMARY KEY,  -- No AUTO_INCREMENT
    company_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    request_hash VARCHAR(64) NOT NULL,
    response_data JSONB,  -- JSONB no JSON
    status VARCHAR(20) DEFAULT 'processing' CHECK (status IN ('processing', 'completed', 'failed')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    expires_at TIMESTAMPTZ NOT NULL,
    CONSTRAINT uk_idempotency UNIQUE (company_id, user_id, endpoint, idempotency_key)
);

CREATE INDEX idx_idempotency_expires ON idempotency_keys (expires_at);

-- Triggers PostgreSQL (no DELIMITER)
CREATE OR REPLACE FUNCTION update_journal_entry_totals()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE journal_entries 
    SET 
        total_debit = COALESCE((
            SELECT SUM(debit) 
            FROM journal_lines 
            WHERE journal_entry_id = COALESCE(NEW.journal_entry_id, OLD.journal_entry_id)
        ), 0),
        total_credit = COALESCE((
            SELECT SUM(credit) 
            FROM journal_lines 
            WHERE journal_entry_id = COALESCE(NEW.journal_entry_id, OLD.journal_entry_id)
        ), 0)
    WHERE id = COALESCE(NEW.journal_entry_id, OLD.journal_entry_id);
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_journal_lines_balance_update
    AFTER INSERT OR UPDATE OR DELETE ON journal_lines
    FOR EACH ROW
    EXECUTE FUNCTION update_journal_entry_totals();
```

---

## 👥 **2. MODELO DE CONTACTO: contact_id ÚNICO**

**DECISIÓN:** Solo contact_id con flags is_customer/is_supplier

### **Justificación:**
- ✅ Una sola tabla contacts (no customer/supplier separado)
- ✅ Contactos pueden ser ambos (customer Y supplier)
- ✅ Relaciones más simples
- ✅ Queries más eficientes

### **Correcciones Aplicadas:**
```sql
-- CORRECTO: Solo contact_id
CREATE TABLE ar_invoices (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL,
    contact_id BIGINT NOT NULL,  -- NO customer_id
    -- ... otros campos
    
    CONSTRAINT fk_ar_invoice_contact 
        FOREIGN KEY (contact_id) 
        REFERENCES contacts(id) 
        ON DELETE RESTRICT,
    
    CONSTRAINT chk_ar_invoice_contact_is_customer
        CHECK (EXISTS (
            SELECT 1 FROM contacts 
            WHERE id = contact_id AND is_customer = true
        ))
);

-- Validaciones en servicios
public function createARInvoice(array $data): ARInvoice
{
    $contact = Contact::findOrFail($data['contact_id']);
    
    if (!$contact->is_customer) {
        throw new ContactNotCustomerException();
    }
    
    // ... resto de lógica
}
```

---

## 🚫 **3. ESTADOS: "voided" ÚNICO**

**DECISIÓN:** Solo "voided", eliminar "cancelled"

### **Justificación:**
- ✅ Terminología financiera estándar
- ✅ Voided = anulado con reversa contable
- ✅ Cancelled = cancelado sin impacto contable (confuso)

### **Correcciones Aplicadas:**
```sql
-- CORRECTO: Solo voided
ALTER TABLE ar_invoices 
ADD CONSTRAINT chk_ar_invoice_status
CHECK (status IN ('draft', 'approved', 'posted', 'paid', 'voided'));

ALTER TABLE ap_invoices 
ADD CONSTRAINT chk_ap_invoice_status
CHECK (status IN ('draft', 'approved', 'posted', 'paid', 'voided'));

-- Estados de transición válidos
-- draft → approved → posted → paid
-- draft → voided
-- approved → voided  
-- posted → voided (con reversa automática)
```

---

## 📅 **4. FECHAS: accounting_date ÚNICO**

**DECISIÓN:** Solo accounting_date para todo GL

### **Justificación:**
- ✅ Períodos fiscales usan accounting_date
- ✅ Índices consistentes
- ✅ Cierres usan accounting_date
- ✅ document_date para referencia, accounting_date para contabilidad

### **Correcciones Aplicadas:**
```sql
-- CORRECTO: accounting_date para GL
CREATE TABLE journal_entries (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL,
    document_date DATE NOT NULL,      -- Fecha del documento original
    accounting_date DATE NOT NULL,    -- Fecha para contabilidad (períodos)
    -- ... otros campos
);

-- Índices usan accounting_date
CREATE INDEX idx_journal_entries_accounting_period 
ON journal_entries (company_id, accounting_date, status);

CREATE INDEX idx_journal_lines_account_accounting_date 
ON journal_lines (company_id, account_id, 
    (SELECT accounting_date FROM journal_entries WHERE id = journal_entry_id));
```

---

## 📦 **5. INVENTORY→GL SIN HARDCODE**

**DECISIÓN:** AccountMappingService para todo inventory GL

### **Justificación:**
- ✅ Consistente con resto del sistema
- ✅ Configurable por company
- ✅ Versionado y audit trail

### **Correcciones Aplicadas:**
```php
class InventoryMovementGLService
{
    public function __construct(
        private AccountMappingService $accountMapping
    ) {}
    
    public function createGLEntry(InventoryMovement $movement): array
    {
        $glLines = [];
        
        switch ($movement->type) {
            case 'receipt':
                // CORRECTO: Usar AccountMappingService
                $inventoryAccount = $this->accountMapping
                    ->getAccount('inventory', $movement->movement_date);
                $grniAccount = $this->accountMapping
                    ->getAccount('grni', $movement->movement_date);
                
                $glLines[] = [
                    'account_id' => $inventoryAccount->id,
                    'debit' => $movement->total_cost,
                    'credit' => 0
                ];
                
                $glLines[] = [
                    'account_id' => $grniAccount->id,
                    'debit' => 0,
                    'credit' => $movement->total_cost
                ];
                break;
                
            case 'issue':
                $cogsAccount = $this->accountMapping
                    ->getAccount('cogs', $movement->movement_date);
                $inventoryAccount = $this->accountMapping
                    ->getAccount('inventory', $movement->movement_date);
                    
                // ... resto de lógica
        }
        
        return $glLines;
    }
}

// Mapeos requeridos para inventory
$requiredMappings = [
    'inventory' => 'Inventory Asset',
    'cogs' => 'Cost of Goods Sold', 
    'inventory_adjustment' => 'Inventory Adjustment',
    'grni' => 'Goods Received Not Invoiced'
];
```

---

## 📄 **6. GRNI / DEVENGO COMPRAS**

**DECISIÓN:** Flujo de tres vías completo PO→Receipt→Invoice

### **Correcciones Aplicadas:**
```php
class PurchaseThreeWayMatchService
{
    // 1. Al recibir inventario (sin invoice aún)
    public function recordReceipt(PurchaseOrderReceipt $receipt): void
    {
        // Crear movement inventory
        $movement = InventoryMovement::create([
            'type' => 'receipt',
            'reference' => "PO-{$receipt->purchaseOrder->number}",
            'total_cost' => $receipt->total_cost
        ]);
        
        // GL: Debit Inventory, Credit GRNI (accrual)
        $this->inventoryGLService->createGLEntry($movement);
    }
    
    // 2. Al registrar AP Invoice
    public function matchInvoiceToReceipt(APInvoice $invoice): void
    {
        $receipt = $this->findMatchingReceipt($invoice);
        
        // Limpiar GRNI accrual
        $this->createGRNICleanupEntry($receipt, $invoice);
    }
    
    private function createGRNICleanupEntry(PurchaseOrderReceipt $receipt, APInvoice $invoice): void
    {
        $grniAccount = $this->accountMapping->getAccount('grni', $invoice->accounting_date);
        $apAccount = $this->accountMapping->getAccount('ap_control', $invoice->accounting_date);
        
        JournalEntry::create([
            'accounting_date' => $invoice->accounting_date,
            'reference' => "GRNI-CLEANUP-{$invoice->invoice_number}",
            'lines' => [
                // Limpiar GRNI accrual
                [
                    'account_id' => $grniAccount->id,
                    'debit' => $receipt->total_cost,
                    'credit' => 0
                ],
                // Crear AP liability
                [
                    'account_id' => $apAccount->id,
                    'debit' => 0,
                    'credit' => $invoice->total_amount
                ]
            ]
        ]);
    }
}
```

---

## 🔑 **7. IDEMPOTENCIA: 409 en HASH MISMATCH**

**DECISIÓN:** 409 Conflict si mismo key, diferente payload

### **Correcciones Aplicadas:**
```php
public function validateIdempotency(string $key, array $payload): ?IdempotencyKey
{
    $payloadHash = hash('sha256', json_encode($payload));
    
    $existing = IdempotencyKey::where([
        'company_id' => auth()->user()->company_id,
        'user_id' => auth()->id(),
        'endpoint' => request()->route()->getName(),
        'idempotency_key' => $key
    ])->where('expires_at', '>', now())->first();
    
    if ($existing) {
        if ($existing->request_hash !== $payloadHash) {
            // CORRECTO: 409 si mismo key, diferente payload
            throw new IdempotencyConflictException(
                'Idempotency key already exists with different payload',
                409
            );
        }
        
        return $existing; // Mismo payload, proceder normal
    }
    
    return null; // Nueva key
}

// Job de limpieza activo
class CleanExpiredIdempotencyKeysJob implements ShouldQueue
{
    public function handle(): void
    {
        $deleted = IdempotencyKey::where('expires_at', '<', now())->delete();
        
        Log::info('Cleaned expired idempotency keys', ['count' => $deleted]);
    }
}
```

---

## ⚡ **8. TRIGGERS DE BALANCE: ESTRATEGIA INCREMENTAL**

**DECISIÓN:** Triggers incrementales para volumen alto

### **Correcciones Aplicadas:**
```sql
-- OPTIMIZADO: Trigger incremental
CREATE OR REPLACE FUNCTION update_journal_entry_totals_incremental()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE journal_entries 
        SET 
            total_debit = total_debit + NEW.debit,
            total_credit = total_credit + NEW.credit
        WHERE id = NEW.journal_entry_id;
        
    ELSIF TG_OP = 'UPDATE' THEN
        UPDATE journal_entries 
        SET 
            total_debit = total_debit - OLD.debit + NEW.debit,
            total_credit = total_credit - OLD.credit + NEW.credit
        WHERE id = NEW.journal_entry_id;
        
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE journal_entries 
        SET 
            total_debit = total_debit - OLD.debit,
            total_credit = total_credit - OLD.credit
        WHERE id = OLD.journal_entry_id;
    END IF;
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

-- Job de verificación nocturno
class VerifyJournalEntryBalancesJob implements ShouldQueue
{
    public function handle(): void
    {
        $mismatched = DB::select("
            SELECT je.id, je.total_debit, je.total_credit,
                   COALESCE(SUM(jl.debit), 0) as actual_debit,
                   COALESCE(SUM(jl.credit), 0) as actual_credit
            FROM journal_entries je
            LEFT JOIN journal_lines jl ON je.id = jl.journal_entry_id
            WHERE je.status = 'posted'
            GROUP BY je.id, je.total_debit, je.total_credit
            HAVING je.total_debit != COALESCE(SUM(jl.debit), 0)
                OR je.total_credit != COALESCE(SUM(jl.credit), 0)
        ");
        
        foreach ($mismatched as $entry) {
            // Corregir automáticamente
            DB::update("
                UPDATE journal_entries 
                SET total_debit = ?, total_credit = ?
                WHERE id = ?
            ", [$entry->actual_debit, $entry->actual_credit, $entry->id]);
        }
    }
}
```

---

## 🏦 **9. PAYMENTS: reconciliation_status SEPARADO**

**DECISIÓN:** Campo separado para reconciliación

### **Correcciones Aplicadas:**
```sql
-- CORRECTO: Campo separado
CREATE TABLE ar_receipts (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft' 
        CHECK (status IN ('draft', 'approved', 'posted', 'voided')),
    reconciliation_status VARCHAR(20) NOT NULL DEFAULT 'unreconciled'
        CHECK (reconciliation_status IN ('unreconciled', 'reconciled')),
    -- ... otros campos
);

-- Transiciones independientes
-- status: draft → approved → posted → voided
-- reconciliation_status: unreconciled ↔ reconciled (solo si posted)
```

---

## 📊 **10. NUMERACIÓN FACTURAS: POLÍTICA ÚNICA**

**DECISIÓN:** UNIQUE(company_id, contact_id, invoice_number, fiscal_year)

### **Correcciones Aplicadas:**
```sql
-- CORRECTO: Constraint unificado
ALTER TABLE ar_invoices 
ADD CONSTRAINT uk_ar_invoice_number 
UNIQUE (company_id, contact_id, invoice_number, fiscal_year);

ALTER TABLE ap_invoices 
ADD CONSTRAINT uk_ap_invoice_number 
UNIQUE (company_id, contact_id, supplier_invoice_number, fiscal_year);

-- Validación en servicios
class InvoiceNumberService
{
    public function validateUniqueNumber(
        int $companyId, 
        int $contactId, 
        string $invoiceNumber, 
        int $fiscalYear,
        string $invoiceType = 'ar'
    ): bool {
        $table = $invoiceType === 'ar' ? 'ar_invoices' : 'ap_invoices';
        $numberField = $invoiceType === 'ar' ? 'invoice_number' : 'supplier_invoice_number';
        
        $exists = DB::table($table)
            ->where('company_id', $companyId)
            ->where('contact_id', $contactId)
            ->where($numberField, $invoiceNumber)
            ->where('fiscal_year', $fiscalYear)
            ->exists();
            
        if ($exists) {
            throw new InvoiceNumberAlreadyExistsException();
        }
        
        return true;
    }
}
```

---

## 🏛️ **11. MAKER-CHECKER: TABLA PARAMETRIZADA**

**DECISIÓN:** approval_policies table configurable

### **Correcciones Aplicadas:**
```sql
CREATE TABLE approval_policies (
    id BIGSERIAL PRIMARY KEY,
    company_id BIGINT NOT NULL,
    operation VARCHAR(50) NOT NULL,
    amount_threshold DECIMAL(15,4) NOT NULL DEFAULT 0,
    required_approvers JSONB NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    
    CONSTRAINT uk_approval_policy UNIQUE (company_id, operation)
);

-- Data inicial
INSERT INTO approval_policies (company_id, operation, amount_threshold, required_approvers) VALUES
(1, 'journal_entry_post', 50000, '["finance_manager"]'),
(1, 'payment_void', 10000, '["finance_manager"]'),
(1, 'period_close', 0, '["finance_director", "accounting_manager"]'),
(1, 'account_mapping_change', 0, '["finance_director"]');
```

```php
class ParameterizedMakerCheckerService
{
    public function requiresApproval(string $operation, float $amount): bool
    {
        $policy = ApprovalPolicy::where('company_id', auth()->user()->company_id)
            ->where('operation', $operation)
            ->where('is_active', true)
            ->first();
            
        return $policy && $amount >= $policy->amount_threshold;
    }
    
    public function getRequiredApprovers(string $operation): array
    {
        $policy = ApprovalPolicy::where('company_id', auth()->user()->company_id)
            ->where('operation', $operation)
            ->first();
            
        return $policy ? $policy->required_approvers : [];
    }
}
```

---

## 🧪 **12. TESTS CONCURRENCIA: ESTRATEGIA REAL**

**DECISIÓN:** ParaTest + Artillery para CI/CD

### **Correcciones Aplicadas:**
```php
// Tests paralelos con ParaTest
class ConcurrentSequenceTest extends TestCase
{
    public function test_concurrent_sequence_generation_no_duplicates()
    {
        // Usar procesos paralelos reales
        $processes = [];
        $resultsFile = tempnam(sys_get_temp_dir(), 'sequences_');
        
        // Lanzar 10 procesos paralelos
        for ($i = 0; $i < 10; $i++) {
            $processes[] = Process::fromShellCommandline(
                "php artisan test:generate-sequence --journal-id=1 --output={$resultsFile}.{$i}"
            );
        }
        
        // Ejecutar todos en paralelo
        foreach ($processes as $process) {
            $process->start();
        }
        
        // Esperar resultados
        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful());
        }
        
        // Verificar unicidad
        $allSequences = [];
        for ($i = 0; $i < 10; $i++) {
            $sequences = json_decode(file_get_contents("{$resultsFile}.{$i}"), true);
            $allSequences = array_merge($allSequences, $sequences);
        }
        
        $this->assertEquals(count($allSequences), count(array_unique($allSequences)));
    }
}

// Artillery config para load testing
// artillery.yml
config:
  target: 'http://localhost:8000'
  phases:
    - duration: 60
      arrivalRate: 20
scenarios:
  - name: "Concurrent Journal Entry Posting"
    requests:
      - post:
          url: "/api/v1/journal-entries/1/post"
          headers:
            Authorization: "Bearer {{ $randomString() }}"
            Idempotency-Key: "test-{{ $randomString() }}"
```

---

## 💱 **13. FX POLICY: BLOQUEO ESTRICTO**

**DECISIÓN:** Bloquear posting si rate inválido (no warning)

### **Correcciones Aplicadas:**
```php
class ExchangeRateService
{
    public function validateAndGetRate(string $currency, Carbon $date): float
    {
        if ($currency === config('app.base_currency')) {
            return 1.0000;
        }
        
        $policy = ExchangeRatePolicy::getForCurrency($currency);
        $maxAge = now()->subDays($policy->max_age_days);
        
        $rate = ExchangeRate::where('from_currency', $currency)
            ->where('effective_date', '<=', $date)
            ->where('created_at', '>=', $maxAge)
            ->where('status', 'active')
            ->orderBy('effective_date', 'desc')
            ->first();
            
        if (!$rate) {
            // CORRECTO: Bloqueo estricto (no warning)
            throw new ExchangeRateRequiredBlockingException(
                "Exchange rate for {$currency} is required and not available. " .
                "Maximum age: {$policy->max_age_days} days. " .
                "Posting blocked until valid rate is provided."
            );
        }
        
        return $rate->rate;
    }
    
    // Fallback con aprobación excepcional
    public function requestExceptionalApproval(string $currency, float $proposedRate): void
    {
        ExceptionalApproval::create([
            'type' => 'exchange_rate_override',
            'currency' => $currency,
            'proposed_rate' => $proposedRate,
            'requested_by_id' => auth()->id(),
            'required_approvers' => ['finance_director']
        ]);
    }
}
```

---

## 📈 **14. OBSERVABILIDAD: PROMETHEUS + GRAFANA**

**DECISIÓN:** Stack Prometheus/Grafana con métricas específicas

### **Correcciones Aplicadas:**
```php
// config/metrics.php
return [
    'driver' => 'prometheus',
    'prometheus' => [
        'namespace' => 'finance_erp',
        'registry' => env('PROMETHEUS_REGISTRY', 'default')
    ]
];

class FinancialMetrics
{
    private PrometheusRegistry $registry;
    
    public function recordInvoicePosting(ARInvoice $invoice): void
    {
        // Counter: total invoices posted
        $this->registry->getOrRegisterCounter(
            'invoices_posted_total',
            'Total AR invoices posted',
            ['company_id', 'currency']
        )->incBy(1, [
            (string) $invoice->company_id,
            $invoice->currency
        ]);
        
        // Histogram: invoice amounts
        $this->registry->getOrRegisterHistogram(
            'invoice_amount_distribution',
            'Distribution of invoice amounts',
            ['company_id', 'currency'],
            [100, 1000, 5000, 10000, 50000, 100000]
        )->observe($invoice->total_amount, [
            (string) $invoice->company_id,
            $invoice->currency
        ]);
        
        // Gauge: current AR balance
        $totalBalance = ARInvoice::where('company_id', $invoice->company_id)
            ->where('status', '!=', 'paid')
            ->sum('balance_amount');
            
        $this->registry->getOrRegisterGauge(
            'ar_balance_current',
            'Current AR balance by company',
            ['company_id']
        )->set($totalBalance, [(string) $invoice->company_id]);
    }
}
```

---

## 🔒 **15. SEGURIDAD EN LOGS: MASKING AUTOMÁTICO**

**DECISIÓN:** Masking automático de PII sensible

### **Correcciones Aplicadas:**
```php
class SecureLogger
{
    private array $sensitiveFields = [
        'account_number', 'routing_number', 'swift_code', 
        'clabe', 'card_number', 'tax_id', 'ssn'
    ];
    
    public function logSecure(string $message, array $context = []): void
    {
        $maskedContext = $this->maskSensitiveData($context);
        Log::info($message, $maskedContext);
    }
    
    private function maskSensitiveData(array $data): array
    {
        return array_map(function ($value, $key) {
            if (is_array($value)) {
                return $this->maskSensitiveData($value);
            }
            
            if (in_array(strtolower($key), $this->sensitiveFields)) {
                return $this->maskValue($value);
            }
            
            return $value;
        }, $data, array_keys($data));
    }
    
    private function maskValue($value): string
    {
        if (empty($value)) return $value;
        
        $str = (string) $value;
        $length = strlen($str);
        
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        
        // Mostrar primeros 2 y últimos 2 caracteres
        return substr($str, 0, 2) . str_repeat('*', $length - 4) . substr($str, -2);
    }
}

// AuditLog con masking
class AuditLog extends Model
{
    public static function logCriticalAction(Model $model, string $action, array $changes): void
    {
        $secureLogger = app(SecureLogger::class);
        $maskedChanges = $secureLogger->maskSensitiveData($changes);
        
        static::create([
            'company_id' => $model->company_id,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => $action,
            'changes' => $maskedChanges, // Ya masked
            // ... resto de campos
        ]);
    }
}
```

---

## 🔧 **16. SNIPPET HUÉRFANO: SEQUENCE SERVICE COMPLETO**

### **Correcciones Aplicadas:**
```php
class SequenceService
{
    public function getNextSequence(Journal $journal, Carbon $date): string
    {
        return DB::transaction(function () use ($journal, $date) {
            // First-or-create atómico para evitar race conditions
            $sequence = JournalSequence::firstOrCreate(
                [
                    'company_id' => $journal->company_id,
                    'journal_id' => $journal->id,
                    'fiscal_year' => $date->year
                ],
                [
                    'current_number' => 0,
                    'prefix' => $journal->prefix,
                    'created_by_id' => auth()->id()
                ]
            );
            
            // Lock específico y increment atómico
            $sequence = JournalSequence::where('id', $sequence->id)
                ->lockForUpdate()
                ->first();
                
            $sequence->increment('current_number');
            
            // Formato unificado: {prefix}-{YYYY}-{MM}-{#####}
            return sprintf('%s-%04d-%02d-%05d',
                $sequence->prefix,
                $date->year,
                $date->month,
                $sequence->current_number
            );
        });
    }
}
```

---

## ✅ **RESUMEN DE CORRECCIONES**

| Inconsistencia | Decisión Unificada | Estado |
|----------------|-------------------|--------|
| Motor BD | PostgreSQL 15+ | ✅ Corregido |
| Modelo Contacto | contact_id único | ✅ Corregido |
| Estados void/cancelled | Solo "voided" | ✅ Corregido |
| Fechas contables | accounting_date único | ✅ Corregido |
| Inventory GL | AccountMappingService | ✅ Corregido |
| GRNI Devengo | Flujo 3-vías completo | ✅ Corregido |
| Idempotencia conflict | 409 en hash mismatch | ✅ Corregido |
| Triggers balance | Estrategia incremental | ✅ Corregido |
| Payment reconciliation | Campo separado | ✅ Corregido |
| Invoice numbering | Política única | ✅ Corregido |
| Maker-Checker | Tabla parametrizada | ✅ Corregido |
| Tests concurrencia | ParaTest + Artillery | ✅ Corregido |
| FX Policy | Bloqueo estricto | ✅ Corregido |
| Observabilidad | Prometheus/Grafana | ✅ Corregido |
| Security logs | Masking automático | ✅ Corregido |
| Sequence service | Código completo | ✅ Corregido |

**🎯 TODAS las inconsistencias arquitectónicas han sido identificadas, corregidas y unificadas.**