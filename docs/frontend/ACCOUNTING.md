# Accounting Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Account | `/api/v1/accounts` | Chart of accounts |
| Journal | `/api/v1/journals` | Journal types |
| JournalEntry | `/api/v1/journal-entries` | GL entries |
| JournalLine | `/api/v1/journal-lines` | Entry lines |
| FiscalPeriod | `/api/v1/fiscal-periods` | Accounting periods |
| ExchangeRate | `/api/v1/exchange-rates` | Currency rates |
| AccountBalance | `/api/v1/account-balances` | Period balances |

## Account (Chart of Accounts)

```typescript
type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
type NormalBalance = 'debit' | 'credit';

interface Account {
  id: string;
  code: string;              // 1101, 2101, etc.
  name: string;
  accountType: AccountType;
  normalBalance: NormalBalance;
  parentId: number | null;   // For hierarchy
  isActive: boolean;
  isHeader: boolean;         // True for parent accounts
  description: string | null;
  currentBalance: number;
  createdAt: string;
}

// List accounts
GET /api/v1/accounts?sort=code

// Get account with children
GET /api/v1/accounts/{id}?include=children,parent

// Create account
POST /api/v1/accounts
{
  "data": {
    "type": "accounts",
    "attributes": {
      "code": "1105",
      "name": "Cuentas por Cobrar",
      "accountType": "asset",
      "normalBalance": "debit",
      "parentId": 1,
      "isActive": true,
      "isHeader": false
    }
  }
}
```

### Account Hierarchy (Mexican Standard)

```
1000 ACTIVO
├── 1100 ACTIVO CIRCULANTE
│   ├── 1101 Caja
│   ├── 1102 Bancos
│   ├── 1104 Clientes
│   └── 1108 Almacen
├── 1200 ACTIVO NO CIRCULANTE
│   └── 1201 Terrenos

2000 PASIVO
├── 2100 PASIVO CIRCULANTE
│   ├── 2101 Proveedores
│   └── 2105 IVA por Pagar

3000 CAPITAL

4000 INGRESOS
├── 4100 Ventas

5000 COSTOS
├── 5100 Costo de Ventas

6000 GASTOS
├── 6100 Gastos de Operacion
```

## Journal Entry

```typescript
type EntryStatus = 'draft' | 'approved' | 'posted' | 'reversed';

interface JournalEntry {
  id: string;
  journalId: number;
  fiscalPeriodId: number;
  number: string;            // Auto: JE-2026-0001
  date: string;
  accountingDate: string;
  reference: string | null;
  description: string;
  totalDebit: number;
  totalCredit: number;
  status: EntryStatus;

  // Approval
  approvedAt: string | null;
  approvedById: number | null;
  postedAt: string | null;
  postedById: number | null;

  // Reversal
  isReversal: boolean;
  reversesEntryId: number | null;
  reversalReason: string | null;

  // Source
  sourceType: string | null; // 'ar_invoice', 'inventory_movement', etc.
  sourceId: number | null;

  createdAt: string;
}

// List entries
GET /api/v1/journal-entries?filter[status]=posted&sort=-date

// Get entry with lines
GET /api/v1/journal-entries/{id}?include=lines.account

// Create entry with lines
POST /api/v1/journal-entries
{
  "data": {
    "type": "journal-entries",
    "attributes": {
      "journalId": 1,
      "fiscalPeriodId": 12,
      "date": "2026-01-08",
      "description": "Sales revenue for January",
      "lines": [
        { "accountId": 5, "debit": 11600.00, "credit": 0 },
        { "accountId": 10, "debit": 0, "credit": 10000.00 },
        { "accountId": 15, "debit": 0, "credit": 1600.00 }
      ]
    }
  }
}
```

### Entry Status Flow

```
draft → approved → posted
                      ↓
                  reversed (creates new reversal entry)
```

### Posting Rules

1. **Debit = Credit**: Total debits must equal total credits
2. **Minimum 2 lines**: At least 2 journal lines required
3. **Period open**: Cannot post to closed/locked period
4. **Immutable**: Posted entries cannot be edited (only reversed)

```typescript
// Approve entry
POST /api/v1/journal-entries/{id}/approve

// Post entry
POST /api/v1/journal-entries/{id}/post

// Reverse entry
POST /api/v1/journal-entries/{id}/reverse
{
  "reason": "Error in original entry",
  "reversal_date": "2026-01-08"
}
```

## Journal Line

```typescript
interface JournalLine {
  id: string;
  journalEntryId: number;
  accountId: number;
  debit: number;             // Either debit OR credit, not both
  credit: number;
  description: string | null;
  createdAt: string;
}

// XOR Rule: Each line must have debit OR credit, never both
// debit > 0 AND credit = 0
// OR
// debit = 0 AND credit > 0
```

## Fiscal Period

```typescript
type PeriodStatus = 'open' | 'closed' | 'locked';

interface FiscalPeriod {
  id: string;
  name: string;              // "January 2026"
  fiscalYear: number;
  periodNumber: number;      // 1-12
  startDate: string;
  endDate: string;
  status: PeriodStatus;
  closedAt: string | null;
  closedById: number | null;
}

// List periods
GET /api/v1/fiscal-periods?filter[fiscal_year]=2026&sort=periodNumber

// Get period summary
GET /api/v1/fiscal-periods/{id}/summary

// Response
{
  "period": { "name": "January 2026", "status": "open" },
  "entries": { "count": 150, "totalDebit": 500000.00 },
  "accounts": {
    "revenue": 100000.00,
    "expenses": 75000.00,
    "netIncome": 25000.00
  }
}

// Close period checklist
GET /api/v1/fiscal-periods/{id}/close-checklist

// Response
{
  "checklist": [
    { "item": "All invoices posted", "status": "complete" },
    { "item": "Bank reconciliation done", "status": "complete" },
    { "item": "Depreciation entries made", "status": "pending" },
    { "item": "Accruals recorded", "status": "pending" }
  ],
  "canClose": false,
  "pendingItems": 2
}

// Close period
POST /api/v1/fiscal-periods/{id}/close

// Reopen period (admin only)
POST /api/v1/fiscal-periods/{id}/reopen
```

### Period Status Rules

| Status | Can Post | Can Edit | Can Delete |
|--------|----------|----------|------------|
| open | Yes | Yes | Yes |
| closed | No* | No | No |
| locked | No | No | No |

\* Reopening requires admin permission

## Exchange Rate

```typescript
interface ExchangeRate {
  id: string;
  fromCurrency: string;      // USD
  toCurrency: string;        // MXN
  rate: number;              // 17.50
  effectiveDate: string;
  source: string | null;     // "Banco de Mexico"
}

// Get current rate
GET /api/v1/exchange-rates?filter[from_currency]=USD&filter[to_currency]=MXN&sort=-effectiveDate&page[size]=1

// Create rate
POST /api/v1/exchange-rates
{
  "data": {
    "type": "exchange-rates",
    "attributes": {
      "fromCurrency": "USD",
      "toCurrency": "MXN",
      "rate": 17.50,
      "effectiveDate": "2026-01-08",
      "source": "Banco de Mexico"
    }
  }
}
```

## Account Mappings

```typescript
// System uses mappings for automatic postings
interface AccountMapping {
  id: string;
  mappingType: string;       // 'ar_invoice', 'inventory_entry', etc.
  debitAccountId: number;
  creditAccountId: number;
  description: string;
}

// Example mappings:
// AR Invoice: Debit 1104 (Clientes), Credit 4100 (Ventas)
// AP Invoice: Debit 5100 (Costos), Credit 2101 (Proveedores)
// Inventory Entry: Debit 1108 (Almacen), Credit 2101 (Proveedores)
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Balanced Entries | Debits = Credits | Validate before save |
| XOR Lines | Debit OR Credit per line | Radio button UI |
| Period Control | Cannot post to closed | Disable closed periods |
| Immutable Posted | Posted entries read-only | Hide edit for posted |
| Reversal Only | Corrections via reversal | Show reverse button |
| Close Checklist | Must complete before close | Show checklist |

## Account Balance Query

```typescript
// Get trial balance
GET /api/v1/account-balances?filter[fiscal_period_id]=12

// Response
{
  "data": [
    {
      "type": "account-balances",
      "attributes": {
        "accountId": 5,
        "accountCode": "1104",
        "accountName": "Clientes",
        "debitBalance": 150000.00,
        "creditBalance": 0,
        "balance": 150000.00
      }
    }
  ]
}

// Get account movement for period
GET /api/v1/journal-lines?filter[account_id]=5&filter[fiscal_period_id]=12&include=journalEntry
```

## Financial Reports (via Reports Module)

```typescript
// Balance Sheet
GET /api/v1/reports/balance-sheets?date=2026-01-08

// Income Statement
GET /api/v1/reports/income-statements?start_date=2026-01-01&end_date=2026-01-31

// Trial Balance
GET /api/v1/reports/trial-balances?fiscal_period_id=12
```
