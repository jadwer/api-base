# 💰 FASE 2: Regeneración Finance Module
## Sistema Financiero Empresarial con GL Integration

**Objetivo:** Regenerar módulo Finance con integración automática a Accounting y preparación CFDI

---

## 🎯 **OBJETIVO**

Regenerar completamente el módulo Finance con estructura empresarial, implementando AR/AP completo con posting automático a GL, aging analysis, aplicación de pagos, y campos preparatorios para facturación CFDI 4.0.

## 📦 **ENTIDADES A REGENERAR**

### **Estructura Financiera Empresarial**
```
FINANCE MODULE:
├── ARInvoice (Facturas por cobrar con aging)
├── ARInvoiceItem (Líneas de factura con GL mapping)  
├── ARInvoicePayment (Aplicación de pagos)
├── ARReceipt (Recibos de cobro)
├── ARReceiptLine (Líneas de recibo)
├── APInvoice (Facturas por pagar con aging)
├── APInvoiceItem (Líneas de factura con GL mapping)
├── APInvoicePayment (Aplicación de pagos)  
├── APPayment (Pagos a proveedores)
├── APPaymentLine (Líneas de pago)
├── BankAccount (Cuentas bancarias)
├── BankTransaction (Transacciones bancarias)
└── PaymentTerm (Términos de pago empresariales)
```

### **Integración GL Automática**
- **AR Invoice → GL:** Debit Customer, Credit Revenue
- **AP Invoice → GL:** Debit Expense, Credit Supplier  
- **AR Receipt → GL:** Debit Bank, Credit Customer
- **AP Payment → GL:** Debit Supplier, Credit Bank

### **Campos CFDI Preparatorios**
- **UUID, Folio Fiscal, Serie/Folio**
- **PAC Integration fields** 
- **SAT Status tracking**
- **XML Storage preparation**

---

## 🛠️ **IMPLEMENTACIÓN**

### **Paso 1: Regeneración con Blueprint Generator**

```bash
# Regenerar usando configuración empresarial con CFDI
php artisan module:advanced-blueprint Finance --config="temp/finance-enterprise-final.json" --force

# Verificar estructura generada
php artisan validate:module-structure Finance
```

### **Paso 2: Implementar Servicios Financieros Empresariales**

#### **2.1 ARInvoiceService (Modelo contact_id Unificado)**
```php
class ARInvoiceService  
{
    public function __construct(
        private AccountingService $accountingService,
        private SequenceService $sequenceService,
        private AccountMappingService $accountMapping
    ) {}
    
    public function postInvoice(ARInvoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            // Idempotencia
            if ($invoice->status === 'posted') {
                return true;
            }
            
            // Validaciones empresariales
            $this->validateInvoiceData($invoice);
            $this->validateContactIsCustomer($invoice);
            
            // Asignar número secuencial
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = $this->sequenceService
                    ->getNextARInvoice($invoice->accounting_date);
            }
            
            // Crear asiento contable automático con AccountMappingService
            $journalEntry = $this->createGLEntry($invoice);
            
            // Post a GL con idempotencia
            $this->accountingService->postJournalEntry($journalEntry);
            
            // Actualizar estado con audit trail
            $invoice->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by_id' => auth()->id(),
                'gl_journal_entry_id' => $journalEntry->id
            ]);
            
            // Trigger eventos empresariales
            event(new ARInvoicePosted($invoice));
            
            return true;
        });
    }
    
    private function createGLEntry(ARInvoice $invoice): JournalEntry
    {
        $entry = JournalEntry::create([
            'company_id' => $invoice->company_id,
            'journal_id' => Journal::where('type', 'sales')->first()->id,
            'fiscal_period_id' => FiscalPeriod::getCurrent()->id,
            'accounting_date' => $invoice->accounting_date, // Unificado: accounting_date
            'reference' => "AR-{$invoice->invoice_number}",
            'description' => "Factura Cliente: {$invoice->contact->name}",
            'source_type' => ARInvoice::class,
            'source_id' => $invoice->id
        ]);
        
        // Usar AccountMappingService (no hardcode)
        $customersAccount = $this->accountMapping->getAccount('ar_control', $invoice->accounting_date);
        
        // Línea Customer (Debit) - contact_id unificado
        $entry->lines()->create([
            'account_id' => $customersAccount->id,
            'contact_id' => $invoice->contact_id, // Unificado: contact_id
            'debit' => $invoice->total_amount,
            'credit' => 0,
            'description' => "Cliente: {$invoice->contact->name}"
        ]);
        
        // Líneas Revenue (Credit) por item usando AccountMappingService
        foreach ($invoice->items as $item) {
            $revenueAccount = $this->accountMapping->getAccount('revenue', $invoice->accounting_date);
            $entry->lines()->create([
                'account_id' => $revenueAccount->id,
                'debit' => 0,
                'credit' => $item->total_amount,
                'description' => $item->description
            ]);
        }
        
        return $entry;
    }
    
    private function validateContactIsCustomer(ARInvoice $invoice): void
    {
        $contact = Contact::findOrFail($invoice->contact_id);
        
        if (!$contact->is_customer) {
            throw new ContactNotCustomerException();
        }
    }
}
```

#### **2.2 PaymentApplicationService**
```php
class PaymentApplicationService
{
    public function applyPayment(ARReceipt $receipt, array $invoiceApplications): bool
    {
        return DB::transaction(function () use ($receipt, $invoiceApplications) {
            $totalApplied = 0;
            
            foreach ($invoiceApplications as $application) {
                $invoice = ARInvoice::findOrFail($application['invoice_id']);
                $amount = $application['amount'];
                
                // Validar que no se sobrepague
                if (($invoice->paid_amount + $amount) > $invoice->total_amount) {
                    throw new PaymentOverApplicationException();
                }
                
                // Crear aplicación
                ARInvoicePayment::create([
                    'ar_invoice_id' => $invoice->id,
                    'ar_receipt_id' => $receipt->id,
                    'amount' => $amount,
                    'applied_at' => now(),
                    'applied_by_id' => auth()->id()
                ]);
                
                // Actualizar balance
                $invoice->increment('paid_amount', $amount);
                $invoice->update([
                    'balance_amount' => $invoice->total_amount - $invoice->paid_amount,
                    'status' => $invoice->balance_amount == 0 ? 'paid' : 'partial'
                ]);
                
                $totalApplied += $amount;
            }
            
            // Crear asiento GL para el pago
            $this->createPaymentGLEntry($receipt, $totalApplied);
            
            return true;
        });
    }
}
```

### **Paso 3: Aging Analysis Empresarial**

```php
class AgingAnalysisService
{
    public function generateARAging($asOfDate = null): Collection
    {
        $asOfDate = $asOfDate ?? now()->toDateString();
        
        return ARInvoice::with('customer')
            ->where('status', '!=', 'paid')
            ->where('issue_date', '<=', $asOfDate)
            ->get()
            ->map(function ($invoice) use ($asOfDate) {
                $daysOverdue = Carbon::parse($asOfDate)
                    ->diffInDays(Carbon::parse($invoice->due_date), false);
                    
                return [
                    'invoice_id' => $invoice->id,
                    'customer' => $invoice->customer->name,
                    'invoice_number' => $invoice->invoice_number,
                    'issue_date' => $invoice->issue_date,
                    'due_date' => $invoice->due_date,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'balance_amount' => $invoice->balance_amount,
                    'days_overdue' => $daysOverdue,
                    'aging_bucket' => $this->getAgingBucket($daysOverdue)
                ];
            });
    }
    
    private function getAgingBucket(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 0 => 'current',
            $daysOverdue <= 30 => '1-30',
            $daysOverdue <= 60 => '31-60', 
            $daysOverdue <= 90 => '61-90',
            default => '90+'
        };
    }
}
```

### **Paso 4: CFDI Integration Preparation**

```php
class CFDIPreparationService
{
    public function prepareCFDIData(ARInvoice $invoice): array
    {
        return [
            // Datos del emisor
            'emisor' => [
                'rfc' => config('cfdi.emisor_rfc'),
                'nombre' => config('cfdi.emisor_nombre'),
                'regimen_fiscal' => config('cfdi.regimen_fiscal')
            ],
            
            // Datos del receptor  
            'receptor' => [
                'rfc' => $invoice->customer->tax_id,
                'nombre' => $invoice->customer->name,
                'uso_cfdi' => $invoice->cfdi_use ?? 'G03'
            ],
            
            // Conceptos
            'conceptos' => $invoice->items->map(function ($item) {
                return [
                    'cantidad' => $item->quantity,
                    'unidad' => $item->unit_code ?? 'ACT',
                    'descripcion' => $item->description,
                    'valor_unitario' => $item->unit_price,
                    'importe' => $item->total_amount,
                    'clave_prod_serv' => $item->sat_product_code ?? '01010101'
                ];
            })->toArray(),
            
            // Impuestos
            'impuestos' => $this->calculateTaxes($invoice),
            
            // Campos preparatorios
            'serie' => $invoice->cfdi_series,
            'folio' => $invoice->cfdi_folio,
            'forma_pago' => $invoice->payment_method ?? '99',
            'metodo_pago' => $invoice->payment_term->cfdi_method ?? 'PUE'
        ];
    }
}
```

---

## ✅ **CRITERIOS DE ACEPTACIÓN**

### **Generación Exitosa**
- [ ] Módulo Finance regenerado con 13 entidades
- [ ] Migraciones con foreign keys a Accounting
- [ ] Schemas con campos CFDI preparatorios
- [ ] Tests base generados (65+ test files)

### **Servicios Financieros**
- [ ] ARInvoiceService con GL posting automático
- [ ] APInvoiceService con GL posting automático
- [ ] PaymentApplicationService funcional
- [ ] AgingAnalysisService implementado

### **Integración GL**
- [ ] AR Invoice posting crea asientos automáticamente
- [ ] AP Invoice posting crea asientos automáticamente
- [ ] Payment application actualiza balances
- [ ] GL entries linkados con source documents

### **CFDI Preparation**
- [ ] Campos CFDI agregados a AR/AP invoices
- [ ] CFDIPreparationService implementado
- [ ] Data structure preparada para PAC integration
- [ ] Validaciones SAT básicas implementadas

---

## 🧪 **TESTING EMPRESARIAL**

### **Tests Críticos de Integración**
```php
class FinanceAccountingIntegrationTest extends TestCase
{
    public function test_ar_invoice_creates_gl_entry_automatically()
    {
        $invoice = ARInvoice::factory()->create();
        
        $this->arInvoiceService->postInvoice($invoice);
        
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => ARInvoice::class,
            'source_id' => $invoice->id
        ]);
    }
    
    public function test_payment_application_updates_balances()
    {
        // Test aplicación de pagos
    }
    
    public function test_aging_analysis_calculation()
    {
        // Test aging buckets
    }
}
```

### **Tests de Performance Financiera**
```php  
class FinancePerformanceTest extends TestCase
{
    public function test_bulk_invoice_posting_performance()
    {
        // Test posting de 1000+ invoices
    }
    
    public function test_aging_analysis_with_large_dataset()
    {
        // Test aging con 10000+ invoices
    }
}
```

---

## 📊 **MÉTRICAS DE ÉXITO**

### **Integration Metrics**
- **GL Posting Success Rate:** 99.9%
- **Payment Application Accuracy:** 100%
- **Balance Calculation Accuracy:** 100%

### **Performance Benchmarks**
- **Invoice Posting:** < 200ms (including GL)
- **Payment Application:** < 150ms per application
- **Aging Analysis:** < 2s for 10,000+ invoices

### **CFDI Readiness**
- **Data Completeness:** 95%+ for required fields
- **SAT Validation:** 100% pass rate for basic rules
- **PAC Integration Readiness:** API structure prepared

---

## 📅 **PLAN DE DESARROLLO**

### **Etapa 1: Regeneración Base**
- Ejecutar blueprint generator
- Configurar foreign keys a Accounting
- Validar estructura generada

### **Etapa 2: Servicios AR (Accounts Receivable)**
- Implementar ARInvoiceService
- GL posting automático para AR
- Tests de integración AR→GL

### **Etapa 3: Servicios AP (Accounts Payable)**  
- Implementar APInvoiceService
- GL posting automático para AP
- Tests de integración AP→GL

### **Etapa 4: Payment Application**
- Implementar PaymentApplicationService
- Balance calculation y updates
- Aging analysis implementation

### **Etapa 5: CFDI Preparation & QA**
- CFDI fields y services
- Testing integration completo
- Performance testing y tuning

---

## 🔗 **INTEGRACIÓN CON SALES/PURCHASE**

### **Sales Integration**
- [ ] SalesOrder→ARInvoice automatic creation
- [ ] Status synchronization (Sales→Finance)
- [ ] Item mapping con GL accounts

### **Purchase Integration**  
- [ ] PurchaseOrder→APInvoice automatic creation
- [ ] Status synchronization (Purchase→Finance) 
- [ ] Item mapping con GL accounts

---

## 🚀 **SIGUIENTE FASE**

Una vez completada la FASE 2, proceder con **FASE 3: Business Rules e Integrations** para completar la implementación empresarial.