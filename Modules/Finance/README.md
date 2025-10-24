# 📦 Finance Module

Advanced module with multiple entities and complex relationships.

**Generated:** 2025-10-24 19:26:35

## 📋 Entities

### ARInvoice
- **Table:** `ar_invoices`
- **Fields:** 14

### APInvoice
- **Table:** `ap_invoices`
- **Fields:** 14

### Payment
- **Table:** `payments`
- **Fields:** 15

### PaymentApplication
- **Table:** `payment_applications`
- **Fields:** 6

### BankAccount
- **Table:** `bank_accounts`
- **Fields:** 10

### PaymentMethod
- **Table:** `payment_methods`
- **Fields:** 5

## 🔗 Relationships

- **ARInvoice** ↔ **Customer** (belongsTo)
- **ARInvoice** ↔ **JournalEntry** (belongsTo)
- **ARInvoice** ↔ **PaymentApplication** (hasMany)
- **APInvoice** ↔ **Supplier** (belongsTo)
- **APInvoice** ↔ **JournalEntry** (belongsTo)
- **Payment** ↔ **Customer** (belongsTo)
- **Payment** ↔ **BankAccount** (belongsTo)
- **Payment** ↔ **PaymentMethod** (belongsTo)
- **Payment** ↔ **JournalEntry** (belongsTo)
- **Payment** ↔ **PaymentApplication** (hasMany)
- **PaymentApplication** ↔ **Payment** (belongsTo)
- **PaymentApplication** ↔ **ARInvoice** (belongsTo)
- **BankAccount** ↔ **Account** (belongsTo)

## 🧪 Testing

```bash
php artisan test Modules/Finance
```
