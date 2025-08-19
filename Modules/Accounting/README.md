# 📦 Accounting Module

Advanced module with multiple entities and complex relationships.

**Generated:** 2025-08-19 12:33:46

## 📋 Entities

### Account
- **Table:** `accounts`
- **Fields:** 9

### FiscalPeriod
- **Table:** `fiscal_periods`
- **Fields:** 5

### Journal
- **Table:** `journals`
- **Fields:** 7

### JournalEntry
- **Table:** `journal_entries`
- **Fields:** 15

### JournalLine
- **Table:** `journal_lines`
- **Fields:** 8

### ExchangeRate
- **Table:** `exchange_rates`
- **Fields:** 4

## 🔗 Relationships

- **JournalEntry** ↔ **JournalLine** (one-to-many)
- **JournalEntry** ↔ **Journal** (many-to-one)
- **JournalEntry** ↔ **FiscalPeriod** (many-to-one)
- **JournalLine** ↔ **Account** (many-to-one)
- **Account** ↔ **Account** (many-to-one)

## 🧪 Testing

```bash
php artisan test Modules/Accounting
```


## 📊 Métricas

- **Test Files**: 30
- **Generated**: 2025-08-19 17:59:33
- **Status**: ✅ Documentation up to date
- **API Version**: JSON:API v1.0
