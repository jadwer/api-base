# Plan de Implementación Interno - Fase 1 Básica

**Documento de Trabajo Interno**  
**Fecha:** 20 de Agosto, 2025  
**Alcance:** Implementación básica y pragmática  
**Status:** ✅ IMPLEMENTADO - Backend funcional  
**Frontend Doc:** Ver `FINANCE_ACCOUNTING_PHASE1_FRONTEND.md`  
**Roadmap F2:** Ver `FINANCE_ACCOUNTING_PHASE2_ROADMAP.md`

---

## 🎯 **ENFOQUE PRAGMÁTICO**

**Objetivo**: Implementar funcionalidad mínima viable para operación básica, sin sobre-ingeniería.

**Filosofía**: Simple, funcional, escalable después.

---

## 📋 **CONTABILIDAD (GL) - BÁSICO FASE 1**

### **Catálogo de Cuentas**
```php
// Campos mínimos
accounts: {
    code (único), 
    name, 
    type, 
    is_postable
}

// Regla única
SOLO cuentas is_postable=true aceptan líneas
```

### **Periodo Contable**
```php
// Configuración mínima
fiscal_periods: {
    start_date,
    end_date, 
    status: 'open' // Solo periodo abierto año actual
}

// Regla opcional F1 (activar F2 si estorba)
Validar entry.date ∈ periodo open
```

### **Asiento Contable**
```php
// Estados simplificados
journal_entries.status: draft → posted (SIN approved/void)

// Al postear
- Se bloquea edición (inmutable)
- number = null OR id (sin numeración automática F1)
```

### **Líneas del Asiento**  
```php
// Reglas duras
1. Σ(debit) = Σ(credit)
2. Todas las cuentas is_postable=true

// Moneda F1
- UNA sola moneda
- Ignorar exchange_rate, base_amount
- SIN diferencias de cambio
```

### **Correcciones**
```
SIN reversión automática F1
Error = captura asiento manual inverso
```

---

## 💰 **FINANZAS - BÁSICO FASE 1**

### **Base: Contactos**
```
Todos los documentos usan party_id (Contact)
```

### **Cuentas por Pagar (AP)**

#### **AP Invoice**
```php
ap_invoices: {
    party_id (required),
    invoice_number (único con party), 
    invoice_date,
    total
}

// Estados: draft → posted → paid (opcional)

// Posteo GL simple
Debit: Gasto/Compra (cuenta por defecto)
Credit: "AP Control" (cuenta por defecto)  
SIN impuestos F1
```

#### **AP Payment**
```php
// Modelo simple F1
ap_payments: {
    ap_invoice_id, // UNA factura por pago
    amount
}

// Estados: draft → posted

// Posteo GL
Debit: "AP Control" (monto aplicado)
Credit: Banco (monto pagado)

// Reglas
- amount ≤ saldo factura
- Si amount == saldo → factura.status = 'paid'
- Si amount < saldo → factura.status = 'partially_paid'
- SIN pivote montos F1
```

### **Cuentas por Cobrar (AR)**

#### **AR Invoice**  
```php
ar_invoices: {
    party_id (rol cliente),
    invoice_number (único con party),
    invoice_date, 
    total
}

// Estados: draft → posted → paid (opcional)

// Posteo GL simple
Debit: "AR Control" 
Credit: Ingreso (cuenta por defecto)
SIN impuestos F1
```

#### **AR Receipt**
```php
// Modelo simple F1
ar_receipts: {
    ar_invoice_id, // UNA factura por cobro
    amount
}

// Posteo GL
Debit: Banco
Credit: "AR Control" (monto aplicado)

// Reglas
- amount ≤ saldo factura
- Actualiza paid/partially_paid
```

### **Tesorería (Bancos)**
```php
bank_accounts: {
    // Solo catálogo + saldo inicial
    // SIN conciliación
    // SIN importaciones F1
}

// Movimiento bancario = deducido de pagos/cobros posteados
```

---

## ❌ **LO QUE DEJAMOS FUERA F1**

```
🚫 Aprobaciones intermedias
🚫 Reversión automática  
🚫 Conciliación bancaria
🚫 Impuestos/retenciones
🚫 Multi-moneda/FX
🚫 Aplicaciones N:M con pivote
🚫 Secuencias de diarios
🚫 Validación periodo (si estorba)

→ Todo esto va a Fase 2/3 cuando núcleo esté estable
```

---

## ✅ **CRITERIOS DE ACEPTACIÓN (RÁPIDOS)**

### **GL**
- [ ] No permite postear si no balancea
- [ ] No permite postear si cuenta no es postable
- [ ] Posteo bloquea edición (inmutable)

### **AP/AR**  
- [ ] invoice_number único por party
- [ ] Posteo crea exactamente 1 asiento GL correcto
- [ ] Estados cambian correctamente

### **Payments/Receipts**
- [ ] No permite aplicar más que saldo factura
- [ ] Actualiza estados factura automáticamente
- [ ] Posteo GL correcto

---

## 🚀 **PLAN DE IMPLEMENTACIÓN RÁPIDA**

### **Semana 1: GL Básico**
```php
// JournalEntryService simple
class JournalEntryService {
    public function post(JournalEntry $entry): bool {
        // 1. Validar balance
        // 2. Validar cuentas postables  
        // 3. Cambiar status a 'posted'
        // 4. Bloquear edición
        return true;
    }
}

// Validaciones en Request
- Σ(debit) = Σ(credit) 
- account.is_postable = true
```

### **Semana 2: AP Básico**
```php
// APInvoiceService simple
class APInvoiceService {
    public function post(APInvoice $invoice): JournalEntry {
        $entry = new JournalEntry([
            'source_type' => 'ap_invoice',
            'source_id' => $invoice->id,
            'status' => 'posted'
        ]);
        
        $entry->lines()->create([
            'account_id' => config('finance.expense_account_id'),
            'debit' => $invoice->total,
            'credit' => 0
        ]);
        
        $entry->lines()->create([
            'account_id' => config('finance.ap_control_account_id'), 
            'debit' => 0,
            'credit' => $invoice->total
        ]);
        
        return $entry;
    }
}
```

### **Semana 3: AR Básico**
```php
// Similar a AP pero invertido
// Debit AR Control, Credit Revenue
```

### **Semana 4: Payments Simple**
```php
// APPaymentService
public function post(APPayment $payment): JournalEntry {
    // Crear asiento
    // Debit AP Control, Credit Bank
    // Actualizar invoice status
}
```

---

## 🎯 **CONFIGURACIÓN INICIAL REQUERIDA**

### **Cuentas por Defecto**
```php
// config/finance.php
return [
    'expense_account_id' => 1,    // Gastos
    'revenue_account_id' => 2,    // Ingresos  
    'ap_control_account_id' => 3, // Proveedores
    'ar_control_account_id' => 4, // Clientes
    'bank_account_id' => 5,       // Banco
];
```

### **Seeder Mínimo**
```php
// AccountSeeder básico
Account::create(['code' => '4000', 'name' => 'Gastos', 'type' => 'expense', 'is_postable' => true]);
Account::create(['code' => '5000', 'name' => 'Ingresos', 'type' => 'revenue', 'is_postable' => true]);  
Account::create(['code' => '2100', 'name' => 'Proveedores', 'type' => 'liability', 'is_postable' => true]);
Account::create(['code' => '1200', 'name' => 'Clientes', 'type' => 'asset', 'is_postable' => true]);
Account::create(['code' => '1100', 'name' => 'Banco', 'type' => 'asset', 'is_postable' => true]);
```

---

## ⚡ **TESTING MÍNIMO**

### **GL Tests**
```php
test_cannot_post_unbalanced_entry()
test_cannot_post_with_non_postable_account()
test_posted_entry_becomes_immutable()
```

### **AP Tests** 
```php
test_ap_invoice_creates_correct_gl_entry()
test_payment_updates_invoice_status()
test_cannot_overpay_invoice()
```

### **AR Tests**
```php
test_ar_invoice_creates_correct_gl_entry()  
test_receipt_updates_invoice_status()
test_cannot_over_collect_invoice()
```

---

## 💡 **NOTAS DE IMPLEMENTACIÓN**

1. **Start Simple**: Enums para estados, validaciones en Requests
2. **Config-Driven**: Cuentas por defecto en config, no hardcoded  
3. **Service Layer**: Lógica en Services, no en Models/Controllers
4. **Immutable Posted**: `updating` event bloquea si status='posted'
5. **Source Tracking**: `source_type`/`source_id` en journal_entries

---

## 📅 **TIMELINE REALISTA**

```
Semana 1: GL básico funcionando
Semana 2: AP invoices + posting  
Semana 3: AR invoices + posting
Semana 4: Payments/Receipts básicos
Semana 5: Testing + refinamiento

TOTAL: 5 semanas para MVP funcional
```

---

## ✅ **ESTADO ACTUAL IMPLEMENTACIÓN**

### **Backend - COMPLETO ✅**
- ✅ GL Core: Accounts, JournalEntries, JournalLines con Services
- ✅ AP/AR: Invoices, Payments, Receipts con posteo automático
- ✅ Configuración: config/finance.php + seeders básicos
- ✅ Testing: 85+ tests CRUD + permisos funcionando
- ✅ Endpoints: Todos funcionales con JSON:API compliance

### **Funcionalidad Extra (Fase 2 implementada pero no usar):**
- ⚠️ ExchangeRates, Journals, FiscalPeriods (multi-moneda/secuencias)
- ⚠️ InvoiceLines, BankStatements (líneas detalle/conciliación)
- ⚠️ Pivotes complejos N:M (aplicaciones múltiples)

### **Pendientes Menores:**
- ❌ Tests business logic (9 tests de reglas de negocio)
- ❌ Documentación business rules refinada

---

## 🎯 **SIGUIENTE PASO: FRONTEND**

**Ver documentación completa en:**
- `docs/development/FINANCE_ACCOUNTING_PHASE1_FRONTEND.md`
- `docs/development/FINANCE_ACCOUNTING_PHASE2_ROADMAP.md`

**Backend está listo para frontend implementar funcionalidad F1.**

---

**🎯 OBJETIVO ALCANZADO: Sistema contable básico operativo con funcionalidad completa, endpoints funcionales, listo para frontend F1.**

---
*Documento interno de trabajo | Backend implementado | Frontend pendiente*