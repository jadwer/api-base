# 🗄️ DATABASE SCHEMA REFERENCE - OFFICIAL DOCUMENTATION
## ⚠️ CRITICAL: READ BEFORE ANY DEVELOPMENT

**Última actualización:** 2025-10-27 (Post Database Corrections)
**Propósito:** Documentación oficial de la estructura de base de datos, modelos y mappings JSON:API

---

## ✅ STATUS: DATABASE CORRECTIONS COMPLETED

**All identified inconsistencies have been corrected as of 2025-10-27.**

### **Completed Corrections:**
1. ✅ Finance module unified to use `contact_id` (Party Pattern)
2. ✅ Migration created and executed (MySQL + SQLite compatible)
3. ✅ All models updated with proper relationships
4. ✅ All schemas updated with correct field mappings
5. ✅ All resources updated with correct attributes
6. ✅ All requests updated with validation rules
7. ✅ All factories updated with Contact references
8. ✅ All tests updated to use `contact_id`

---

## 🎯 ARCHITECTURE DECISION: PARTY PATTERN

**The system uses Party Pattern for contact management:**

- **Single unified table:** `contacts`
- **Role flags:** `is_customer`, `is_supplier` (can be both)
- **Unified foreign key:** `contact_id` across all modules

### **Why Party Pattern?**

1. **Consistency:** Sales, Purchase already use `contact_id`
2. **Flexibility:** A contact can be both customer AND supplier
3. **Simplicity:** Single JOIN target for all modules
4. **Reporting:** Unified contact-level reporting

### **Validation Strategy:**

```php
// ARInvoiceRequest validates is_customer
'contactId' => [
    'required',
    'exists:contacts,id',
    function ($attribute, $value, $fail) {
        $contact = Contact::find($value);
        if (!$contact || !$contact->is_customer) {
            $fail('El contacto debe ser un cliente válido.');
        }
    }
]

// APInvoiceRequest validates is_supplier
// Similar validation but checks is_supplier
```

---

## 📋 FINANCE MODULE - CORRECTED SCHEMA

### **1. AR Invoices (Accounts Receivable)**

**Tabla:** `ar_invoices`

| Campo | Tipo | Nullable | Default | Descripción |
|-------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | PK |
| invoice_number | varchar(255) | NO | - | Número único de factura |
| invoice_date | date | NO | - | Fecha de factura |
| due_date | date | NO | - | Fecha de vencimiento |
| **contact_id** | **bigint unsigned** | **NO** | - | **FK a contacts (customer)** ✅ |
| **sales_order_id** | **bigint unsigned** | **YES** | **NULL** | **FK a sales_orders** ✅ |
| currency | varchar(255) | NO | MXN | Moneda |
| subtotal | decimal(10,2) | NO | - | Subtotal |
| tax_amount | decimal(10,2) | NO | - | Impuestos |
| total_amount | decimal(10,2) | NO | - | Total |
| paid_amount | decimal(10,2) | YES | 0.00 | Monto pagado |
| status | varchar(255) | NO | draft | Estado |
| journal_entry_id | bigint unsigned | YES | NULL | FK a journal_entries |
| notes | text | YES | NULL | Notas |
| metadata | json | YES | NULL | Metadata JSON |
| is_active | tinyint(1) | YES | 1 | Activo |
| created_at | timestamp | YES | NULL | Fecha creación |
| updated_at | timestamp | YES | NULL | Fecha actualización |

**Modelo:** `Modules\Finance\Models\ARInvoice`

**Relaciones:**
```php
public function contact() // PRIMARY
public function customer() // LEGACY ALIAS
public function salesOrder()
public function journalEntry()
public function paymentApplications()
```

**JSON:API Schema:** `Modules\Finance\JsonApi\V1\ARInvoices\ARInvoiceSchema`
- Type: `ar-invoices`
- Fields: `contactId`, `salesOrderId`, etc.
- Relationships: `contact`, `salesOrder`, `journalEntry`, `paymentApplications`

---

### **2. AP Invoices (Accounts Payable)**

**Tabla:** `ap_invoices`

| Campo | Tipo | Nullable | Default | Descripción |
|-------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | PK |
| invoice_number | varchar(255) | NO | - | Número único de factura |
| invoice_date | date | NO | - | Fecha de factura |
| due_date | date | NO | - | Fecha de vencimiento |
| **contact_id** | **bigint unsigned** | **NO** | - | **FK a contacts (supplier)** ✅ |
| **purchase_order_id** | **bigint unsigned** | **YES** | **NULL** | **FK a purchase_orders** ✅ |
| currency | varchar(255) | NO | MXN | Moneda |
| subtotal | decimal(10,2) | NO | - | Subtotal |
| tax_amount | decimal(10,2) | NO | - | Impuestos |
| total_amount | decimal(10,2) | NO | - | Total |
| paid_amount | decimal(10,2) | YES | 0.00 | Monto pagado |
| status | varchar(255) | NO | draft | Estado |
| journal_entry_id | bigint unsigned | YES | NULL | FK a journal_entries |
| notes | text | YES | NULL | Notas |
| metadata | json | YES | NULL | Metadata JSON |
| is_active | tinyint(1) | YES | 1 | Activo |
| created_at | timestamp | YES | NULL | Fecha creación |
| updated_at | timestamp | YES | NULL | Fecha actualización |

**Modelo:** `Modules\Finance\Models\APInvoice`

**Relaciones:**
```php
public function contact() // PRIMARY
public function supplier() // LEGACY ALIAS
public function purchaseOrder()
public function journalEntry()
```

**JSON:API Schema:** `Modules\Finance\JsonApi\V1\APInvoices\APInvoiceSchema`
- Type: `ap-invoices`
- Fields: `contactId`, `purchaseOrderId`, etc.
- Relationships: `contact`, `purchaseOrder`, `journalEntry`

---

### **3. Payments**

**Tabla:** `payments`

| Campo | Tipo | Nullable | Default | Descripción |
|-------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | PK |
| payment_number | varchar(255) | YES | - | Número de pago |
| payment_date | date | YES | - | Fecha de pago |
| **contact_id** | **bigint unsigned** | **NO** | - | **FK a contacts (customer)** ✅ |
| bank_account_id | bigint unsigned | YES | NULL | FK a bank_accounts |
| payment_method_id | bigint unsigned | YES | NULL | FK a payment_methods |
| amount | decimal(10,2) | YES | - | Monto |
| currency | varchar(255) | YES | MXN | Moneda |
| applied_amount | decimal(10,2) | YES | 0.00 | Monto aplicado |
| unapplied_amount | decimal(10,2) | YES | 0.00 | Monto no aplicado |
| status | varchar(255) | YES | unapplied | Estado |
| journal_entry_id | bigint unsigned | YES | NULL | FK a journal_entries |
| reference | varchar(255) | YES | NULL | Referencia |
| notes | text | YES | NULL | Notas |
| metadata | json | YES | NULL | Metadata JSON |
| is_active | tinyint(1) | YES | 1 | Activo |
| created_at | timestamp | YES | NULL | Fecha creación |
| updated_at | timestamp | YES | NULL | Fecha actualización |

**Modelo:** `Modules\Finance\Models\Payment`

**Relaciones:**
```php
public function contact() // PRIMARY
public function customer() // LEGACY ALIAS
public function bankAccount()
public function paymentMethod()
public function journalEntry()
public function paymentApplications()
```

**JSON:API Schema:** `Modules\Finance\JsonApi\V1\Payments\PaymentSchema`
- Type: `payments`
- Fields: `contactId`, etc.
- Relationships: `contact`, `bankAccount`, `paymentMethod`, `journalEntry`, `paymentApplications`

---

## 📋 PURCHASE MODULE - CORRECTED SCHEMA

### **Purchase Orders**

**Tabla:** `purchase_orders`

| Campo | Tipo | Nullable | Default | Descripción |
|-------|------|----------|---------|-------------|
| id | bigint unsigned | NO | auto | PK |
| order_number | varchar(255) | NO | - | Número de orden |
| order_date | date | NO | - | Fecha de orden |
| **contact_id** | **bigint unsigned** | **NO** | - | **FK a contacts (supplier)** ✅ |
| status | varchar(255) | NO | pending | Estado |
| **financial_status** | **varchar(255)** | **NO** | **not_invoiced** | **Estado financiero** ✅ |
| invoicing_status | varchar(255) | NO | not_invoiced | Estado facturación |
| total_amount | decimal(10,2) | NO | - | Total |
| **ap_invoice_id** | **bigint unsigned** | **YES** | **NULL** | **FK a ap_invoices** ✅ |
| notes | text | YES | NULL | Notas |
| created_at | timestamp | YES | NULL | Fecha creación |
| updated_at | timestamp | YES | NULL | Fecha actualización |

**Modelo:** `Modules\Purchase\Models\PurchaseOrder`

**Relaciones:**
```php
public function contact() // PRIMARY
public function supplier() // LEGACY ALIAS
public function purchaseOrderItems()
public function apInvoice()
```

---

## 📋 PRODUCT MODULE - SCHEMA CLARIFICATION

### **Products**

**Tabla:** `products`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint unsigned | PK |
| name | varchar(255) | Nombre del producto |
| **price** | **double** | **Precio de venta** ✅ |
| **cost** | **double** | **Costo de adquisición** ✅ |
| ❌ ~~unit_price~~ | - | **NO EXISTE - usar price o cost** |

**NOTA IMPORTANTE:** El campo `unit_price` NO existe en la tabla products. Los campos correctos son:
- `price`: Precio de venta al cliente
- `cost`: Costo de adquisición

**Items de órdenes (sales_order_items, purchase_order_items) SÍ tienen `unit_price`:**
- Este campo almacena el precio específico acordado en esa transacción
- Puede diferir del `price` o `cost` del producto

---

## 🔧 MIGRATION REFERENCE

**Migración ejecutada:** `2025_10_27_100000_fix_finance_contact_references.php`

**Cambios aplicados:**
1. `ar_invoices.customer_id` → `contact_id`
2. `ar_invoices` + `sales_order_id` column
3. `ap_invoices.supplier_id` → `contact_id`
4. `ap_invoices` + `purchase_order_id` column
5. `payments.customer_id` → `contact_id`

**Compatibilidad:**
- ✅ MySQL/MariaDB: Usa `ALTER TABLE ... CHANGE COLUMN`
- ✅ SQLite: Usa `Schema::table()->renameColumn()`

---

## 📚 RELATED DOCUMENTATION

- **Phase Planning:** `docs/roadmaps/phases/PHASE_2_FINANCE.md`
- **Module Blueprint:** `docs/development/module-blueprint-master.md`
- **Testing Strategy:** `docs/development/PHASE3_TESTING_STRATEGY.md`

---

## ✅ VERIFICATION CHECKLIST

Before starting any new development, verify:

- [ ] Models use `contact_id` (not customer_id/supplier_id)
- [ ] Schemas map `contactId` in JSON:API
- [ ] Resources output `contactId` attribute
- [ ] Requests validate `is_customer`/`is_supplier`
- [ ] Factories use `Contact::factory()->customer()` or `->supplier()`
- [ ] Tests assert on `contact_id` in database

---

**End of Document** | Last Updated: 2025-10-27 | Status: ✅ CORRECTED
