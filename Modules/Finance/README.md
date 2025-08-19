# 📦 Finance Module

Advanced module with multiple entities and complex relationships.

**Generated:** 2025-08-19 12:33:58

## 📋 Entities

### BankAccount
- **Table:** `bank_accounts`
- **Fields:** 7

### BankStatement
- **Table:** `bank_statements`
- **Fields:** 3

### BankStatementLine
- **Table:** `bank_statement_lines`
- **Fields:** 7

### APInvoice
- **Table:** `ap_invoices`
- **Fields:** 10

### APInvoiceLine
- **Table:** `ap_invoice_lines`
- **Fields:** 6

### APPayment
- **Table:** `ap_payments`
- **Fields:** 7

### APInvoicePayment
- **Table:** `ap_invoice_payments`
- **Fields:** 5

### ARInvoice
- **Table:** `ar_invoices`
- **Fields:** 10

### ARInvoiceLine
- **Table:** `ar_invoice_lines`
- **Fields:** 6

### ARReceipt
- **Table:** `ar_receipts`
- **Fields:** 7

### ARInvoiceReceipt
- **Table:** `ar_invoice_receipts`
- **Fields:** 5

## 🔗 Relationships

- **BankStatement** ↔ **BankStatementLine** (one-to-many)
- **BankStatement** ↔ **BankAccount** (many-to-one)
- **APInvoice** ↔ **APInvoiceLine** (one-to-many)
- **ARInvoice** ↔ **ARInvoiceLine** (one-to-many)
- **APPayment** ↔ **APInvoicePayment** (one-to-many)
- **APInvoice** ↔ **APInvoicePayment** (one-to-many)
- **ARReceipt** ↔ **ARInvoiceReceipt** (one-to-many)
- **ARInvoice** ↔ **ARInvoiceReceipt** (one-to-many)
- **APPayment** ↔ **BankAccount** (many-to-one)
- **ARReceipt** ↔ **BankAccount** (many-to-one)

## 🧪 Testing

```bash
php artisan test Modules/Finance
```


## 📊 Métricas

- **Test Files**: 55
- **Generated**: 2025-08-19 17:59:33
- **Status**: ✅ Documentation up to date
- **API Version**: JSON:API v1.0
