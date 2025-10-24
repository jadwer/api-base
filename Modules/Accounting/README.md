# 📦 Accounting Module

Advanced module with multiple entities and complex relationships.

**Generated:** 2025-10-24 10:17:21

## 📋 Entities

### IdempotencyKey
- **Table:** `idempotency_keys`
- **Fields:** 8

### AccountMapping
- **Table:** `account_mappings`
- **Fields:** 9

### AccountBalance
- **Table:** `account_balances`
- **Fields:** 8

### ExchangeRatePolicy
- **Table:** `exchange_rate_policies`
- **Fields:** 8

### AuditLog
- **Table:** `audit_logs`
- **Fields:** 12

### Account
- **Table:** `accounts`
- **Fields:** 12

### FiscalPeriod
- **Table:** `fiscal_periods`
- **Fields:** 10

### Journal
- **Table:** `journals`
- **Fields:** 7

### JournalSequence
- **Table:** `journal_sequences`
- **Fields:** 4

### JournalEntry
- **Table:** `journal_entries`
- **Fields:** 17

### JournalLine
- **Table:** `journal_lines`
- **Fields:** 8

### ExchangeRate
- **Table:** `exchange_rates`
- **Fields:** 7

## 🔗 Relationships

- **Account** ↔ **Account** (hasMany)
- **Account** ↔ **Account** (belongsTo)
- **JournalSequence** ↔ **Journal** (belongsTo)
- **Journal** ↔ **JournalSequence** (hasMany)
- **JournalEntry** ↔ **Journal** (belongsTo)
- **JournalEntry** ↔ **FiscalPeriod** (belongsTo)
- **JournalEntry** ↔ **JournalEntry** (belongsTo)
- **JournalEntry** ↔ **JournalEntry** (hasOne)
- **Journal** ↔ **JournalEntry** (hasMany)
- **FiscalPeriod** ↔ **JournalEntry** (hasMany)
- **JournalLine** ↔ **JournalEntry** (belongsTo)
- **JournalLine** ↔ **Account** (belongsTo)
- **JournalEntry** ↔ **JournalLine** (hasMany)
- **Account** ↔ **JournalLine** (hasMany)

## 🧪 Testing

```bash
php artisan test Modules/Accounting
```
