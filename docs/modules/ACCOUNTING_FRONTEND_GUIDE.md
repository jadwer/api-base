# Accounting Module - Frontend Integration Guide

**Module:** Accounting
**Entities:** 12 (Account, JournalEntry, JournalLine, Journal, JournalSequence, FiscalPeriod, ExchangeRate, ExchangeRatePolicy, AccountBalance, AccountMapping, AuditLog, IdempotencyKey)
**Endpoints:** 60
**Base Path:** `/api/v1`

## Overview

The Accounting module provides double-entry bookkeeping functionality including chart of accounts, journal entries, fiscal period management, exchange rates, and audit trails. All financial transactions from other modules (Sales, Purchase, Finance, HR) automatically post to the general ledger.

## Core Entities

### 1. Account

**Endpoint:** `/accounts`
**Resource Type:** `accounts`

#### TypeScript Interface

```typescript
type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
type Nature = 'debit' | 'credit';
type AccountStatus = 'active' | 'inactive' | 'archived';

interface Account {
  id: string;
  code: string;
  name: string;
  accountType: AccountType;
  nature: Nature;
  level: number;
  parentId: number | null;
  currency: string;
  isPostable: boolean;
  isCashFlow: boolean;
  status: AccountStatus;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `code` | `code` | string | Yes | Yes | Yes |
| `name` | `name` | string | Yes | Yes | Yes |
| `accountType` | `account_type` | string | Yes | Yes | Yes |
| `nature` | `nature` | string | Yes | Yes | Yes |
| `level` | `level` | number | Yes | Yes | Yes |
| `parentId` | `parent_id` | number | No | No | No |
| `currency` | `currency` | string | Yes | Yes | Yes |
| `isPostable` | `is_postable` | boolean | No | Yes | Yes |
| `isCashFlow` | `is_cash_flow` | boolean | No | Yes | Yes |
| `status` | `status` | string | Yes | Yes | Yes |

#### Relationships

- `account` → Account (belongsTo) - Parent account
- `accounts` → Account[] (hasMany) - Sub-accounts
- `journalLines` → JournalLine[] (hasMany)

#### Examples

**Get Chart of Accounts:**
```javascript
const response = await fetch(
  '/api/v1/accounts?filter[status]=active&sort=code',
  { headers }
);

const accounts = await response.json();
```

**Create New Account:**
```javascript
const payload = {
  data: {
    type: "accounts",
    attributes: {
      code: "1100",
      name: "Cash",
      accountType: "asset",
      nature: "debit",
      level: 1,
      parentId: null,
      currency: "USD",
      isPostable: true,
      isCashFlow: true,
      status: "active"
    }
  }
};

const response = await fetch('/api/v1/accounts', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

---

### 2. Journal

**Endpoint:** `/journals`
**Resource Type:** `journals`

#### TypeScript Interface

```typescript
type JournalType = 'general' | 'sales' | 'purchases' | 'cash' | 'bank' | 'payroll';
type JournalStatus = 'active' | 'inactive';

interface Journal {
  id: string;
  code: string;
  name: string;
  description: string | null;
  prefix: string;
  type: JournalType;
  status: JournalStatus;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `code` | `code` | string | Yes | Yes |
| `name` | `name` | string | Yes | Yes |
| `description` | `description` | string | No | No |
| `prefix` | `prefix` | string | Yes | Yes |
| `type` | `type` | string | Yes | Yes |
| `status` | `status` | string | Yes | Yes |

#### Relationships

- `journalSequences` → JournalSequence[] (hasMany)
- `journalEntries` → JournalEntry[] (hasMany)

---

### 3. JournalSequence

**Endpoint:** `/journal-sequences`
**Resource Type:** `journal-sequences`

#### TypeScript Interface

```typescript
interface JournalSequence {
  id: string;
  journalId: number;
  fiscalYear: number;
  currentNumber: number;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `journalId` | `journal_id` | number | No | No |
| `fiscalYear` | `fiscal_year` | number | Yes | Yes |
| `currentNumber` | `current_number` | number | Yes | Yes |

#### Relationships

- `journal` → Journal (belongsTo)

---

### 4. JournalEntry

**Endpoint:** `/journal-entries`
**Resource Type:** `journal-entries`

#### TypeScript Interface

```typescript
type JournalEntryStatus = 'draft' | 'pending' | 'approved' | 'posted' | 'reversed';

interface JournalEntry {
  id: string;
  journalId: number;
  fiscalPeriodId: number;
  number: string;
  date: string;
  reference: string | null;
  description: string;
  totalDebit: number;      // Read-only, auto-calculated
  totalCredit: number;     // Read-only, auto-calculated
  status: JournalEntryStatus;
  approvedAt: string | null;
  approvedById: number | null;
  postedAt: string | null;
  postedById: number | null;
  reversalOfId: number | null;
  reversalReason: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `journalId` | `journal_id` | number | Yes | No | No |
| `fiscalPeriodId` | `fiscal_period_id` | number | Yes | No | No |
| `number` | `number` | string | Yes | Yes | Yes |
| `date` | `date` | date | Yes | Yes | No |
| `reference` | `reference` | string | No | Yes | Yes |
| `description` | `description` | string | Yes | No | No |
| `totalDebit` | `total_debit` | number | - | Yes | No |
| `totalCredit` | `total_credit` | number | - | Yes | No |
| `status` | `status` | string | Yes | Yes | Yes |
| `postedAt` | `posted_at` | datetime | No | Yes | No |

#### Relationships

- `journal` → Journal (belongsTo)
- `fiscalPeriod` → FiscalPeriod (belongsTo)
- `journalEntry` → JournalEntry (belongsTo) - For reversals
- `journalLines` → JournalLine[] (hasMany)

---

### 5. JournalLine

**Endpoint:** `/journal-lines`
**Resource Type:** `journal-lines`

#### TypeScript Interface

```typescript
interface JournalLine {
  id: string;
  journalEntryId: number;
  accountId: number;
  contactId: number | null;
  debit: number;
  credit: number;
  description: string | null;
  reference: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `journalEntryId` | `journal_entry_id` | number | No | No |
| `accountId` | `account_id` | number | No | No |
| `contactId` | `contact_id` | number | No | No |
| `debit` | `debit` | number | Yes | No |
| `credit` | `credit` | number | Yes | No |
| `reference` | `reference` | string | Yes | Yes |

#### Relationships

- `journalEntry` → JournalEntry (belongsTo)
- `account` → Account (belongsTo)

---

### 6. FiscalPeriod

**Endpoint:** `/fiscal-periods`
**Resource Type:** `fiscal-periods`

#### TypeScript Interface

```typescript
type FiscalPeriodStatus = 'open' | 'closed' | 'locked';

interface FiscalPeriod {
  id: string;
  name: string;
  year: number;
  month: number;
  startDate: string;
  endDate: string;
  status: FiscalPeriodStatus;
  closedAt: string | null;
  closedById: number | null;
  closingEntryId: number | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `name` | `name` | string | Yes | Yes |
| `year` | `year` | number | Yes | Yes |
| `month` | `month` | number | Yes | Yes |
| `startDate` | `start_date` | date | Yes | No |
| `endDate` | `end_date` | date | Yes | No |
| `status` | `status` | string | Yes | Yes |
| `closedAt` | `closed_at` | datetime | Yes | No |

#### Relationships

- `journalEntries` → JournalEntry[] (hasMany)

---

### 7. ExchangeRate

**Endpoint:** `/exchange-rates`
**Resource Type:** `exchange-rates`

#### TypeScript Interface

```typescript
type ExchangeRateStatus = 'active' | 'inactive';

interface ExchangeRate {
  id: string;
  fromCurrency: string;
  toCurrency: string;
  rate: number;
  effectiveDate: string;
  source: string | null;
  status: ExchangeRateStatus;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `fromCurrency` | `from_currency` | string | Yes | Yes |
| `toCurrency` | `to_currency` | string | Yes | Yes |
| `rate` | `rate` | number | Yes | No |
| `effectiveDate` | `effective_date` | date | Yes | No |
| `source` | `source` | string | Yes | Yes |
| `status` | `status` | string | Yes | Yes |

---

### 8. ExchangeRatePolicy

**Endpoint:** `/exchange-rate-policies`
**Resource Type:** `exchange-rate-policies`

#### TypeScript Interface

```typescript
interface ExchangeRatePolicy {
  id: string;
  currency: string;
  source: string;
  scope: string;
  maxAgeDays: number;
  tolerancePercentage: number;
  requireApprovalOver: number;
  isActive: boolean;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `currency` | `currency` | string | Yes | Yes |
| `source` | `source` | string | Yes | Yes |
| `scope` | `scope` | string | Yes | Yes |
| `maxAgeDays` | `max_age_days` | number | Yes | Yes |
| `tolerancePercentage` | `tolerance_percentage` | number | Yes | No |
| `requireApprovalOver` | `require_approval_over` | number | Yes | No |
| `isActive` | `is_active` | boolean | Yes | Yes |

---

### 9. AccountBalance

**Endpoint:** `/account-balances`
**Resource Type:** `account-balances`

#### TypeScript Interface

```typescript
interface AccountBalance {
  id: string;
  accountId: number;
  fiscalYear: number;
  fiscalMonth: number;
  openingBalance: number;
  periodDebits: number;
  periodCredits: number;
  closingBalance: number;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `accountId` | `account_id` | number | No | No |
| `fiscalYear` | `fiscal_year` | number | Yes | Yes |
| `fiscalMonth` | `fiscal_month` | number | Yes | Yes |
| `openingBalance` | `opening_balance` | number | Yes | No |
| `periodDebits` | `period_debits` | number | Yes | No |
| `periodCredits` | `period_credits` | number | Yes | No |
| `closingBalance` | `closing_balance` | number | Yes | No |

---

### 10. AccountMapping

**Endpoint:** `/account-mappings`
**Resource Type:** `account-mappings`

#### TypeScript Interface

```typescript
interface AccountMapping {
  id: string;
  mappingType: string;
  accountId: number;
  version: number;
  effectiveFrom: string;
  effectiveTo: string | null;
  isActive: boolean;
  createdById: number;
  notes: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `mappingType` | `mapping_type` | string | Yes | Yes |
| `accountId` | `account_id` | number | No | No |
| `version` | `version` | number | Yes | Yes |
| `effectiveFrom` | `effective_from` | datetime | Yes | No |
| `effectiveTo` | `effective_to` | datetime | Yes | No |
| `isActive` | `is_active` | boolean | Yes | Yes |

---

### 11. AuditLog

**Endpoint:** `/audit-logs`
**Resource Type:** `audit-logs`

#### TypeScript Interface

```typescript
interface AuditLog {
  id: string;
  modelType: string;
  modelId: number;
  action: string;
  userId: number;
  changes: Record<string, any>;
  ipAddress: string | null;
  userAgent: string | null;
  sessionId: string | null;
  payloadHash: string | null;
  requiresRetention: boolean;
  retentionUntil: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `modelType` | `model_type` | string | Yes | Yes |
| `modelId` | `model_id` | number | Yes | Yes |
| `action` | `action` | string | Yes | Yes |
| `userId` | `user_id` | number | No | No |
| `ipAddress` | `ip_address` | string | Yes | Yes |
| `sessionId` | `session_id` | string | Yes | Yes |
| `payloadHash` | `payload_hash` | string | Yes | Yes |
| `requiresRetention` | `requires_retention` | boolean | Yes | Yes |
| `retentionUntil` | `retention_until` | datetime | Yes | No |

---

### 12. IdempotencyKey

**Endpoint:** `/idempotency-keys`
**Resource Type:** `idempotency-keys`

#### TypeScript Interface

```typescript
type IdempotencyStatus = 'pending' | 'completed' | 'failed';

interface IdempotencyKey {
  id: string;
  userId: number;
  endpoint: string;
  idempotencyKey: string;
  requestHash: string;
  responseData: Record<string, any> | null;
  status: IdempotencyStatus;
  expiresAt: string;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Sortable | Filterable |
|---------------|-----------------|------|----------|------------|
| `userId` | `user_id` | number | No | No |
| `endpoint` | `endpoint` | string | Yes | Yes |
| `idempotencyKey` | `idempotency_key` | string | Yes | Yes |
| `requestHash` | `request_hash` | string | Yes | Yes |
| `status` | `status` | string | Yes | Yes |
| `expiresAt` | `expires_at` | datetime | Yes | No |

---

## Common Use Cases

### 1. Create Journal Entry (Manual GL Posting)

```javascript
async function createJournalEntry(entryData) {
  // Must balance: totalDebit === totalCredit
  const totalDebit = entryData.lines
    .reduce((sum, line) => sum + (line.debit || 0), 0);
  const totalCredit = entryData.lines
    .reduce((sum, line) => sum + (line.credit || 0), 0);

  if (Math.abs(totalDebit - totalCredit) > 0.01) {
    throw new Error('Journal entry must balance: debits must equal credits');
  }

  // 1. Create journal entry
  const entryPayload = {
    data: {
      type: "journal-entries",
      attributes: {
        journalId: entryData.journalId,
        fiscalPeriodId: entryData.fiscalPeriodId,
        number: entryData.number,
        date: entryData.date,
        reference: entryData.reference,
        description: entryData.description,
        status: "draft"
      }
    }
  };

  const entryResponse = await fetch('/api/v1/journal-entries', {
    method: 'POST',
    headers,
    body: JSON.stringify(entryPayload)
  });

  const entry = await entryResponse.json();
  const entryId = entry.data.id;

  // 2. Create journal lines
  for (const line of entryData.lines) {
    const linePayload = {
      data: {
        type: "journal-lines",
        attributes: {
          journalEntryId: parseInt(entryId),
          accountId: line.accountId,
          contactId: line.contactId || null,
          debit: line.debit || 0,
          credit: line.credit || 0,
          description: line.description,
          reference: line.reference
        }
      }
    };

    await fetch('/api/v1/journal-lines', {
      method: 'POST',
      headers,
      body: JSON.stringify(linePayload)
    });
  }

  return entry;
}

// Usage example
const journalEntry = await createJournalEntry({
  journalId: 1,
  fiscalPeriodId: 5,
  number: "JE-2025-001",
  date: "2025-11-05",
  reference: "Invoice payment",
  description: "Payment received from customer",
  lines: [
    { accountId: 1, debit: 1000, credit: 0, description: "Cash" },
    { accountId: 4, debit: 0, credit: 1000, description: "Accounts Receivable" }
  ]
});
```

### 2. Get Account Balances

```javascript
async function getAccountBalances(fiscalYear, fiscalMonth) {
  const response = await fetch(
    `/api/v1/account-balances?filter[fiscal_year]=${fiscalYear}&filter[fiscal_month]=${fiscalMonth}`,
    { headers }
  );

  return await response.json();
}
```

### 3. Close Fiscal Period

```javascript
async function closeFiscalPeriod(fiscalPeriodId) {
  // 1. Verify all entries are posted
  const entriesResponse = await fetch(
    `/api/v1/journal-entries?filter[fiscalPeriodId]=${fiscalPeriodId}&include=fiscalPeriod`,
    { headers }
  );

  const entries = await entriesResponse.json();
  const hasUnpostedEntries = entries.data.some(
    entry => entry.attributes.status !== 'posted'
  );

  if (hasUnpostedEntries) {
    throw new Error('Cannot close period: some entries are not posted');
  }

  // 2. Close the period
  const payload = {
    data: {
      type: "fiscal-periods",
      id: fiscalPeriodId,
      attributes: {
        status: "closed",
        closedAt: new Date().toISOString()
      }
    }
  };

  const response = await fetch(`/api/v1/fiscal-periods/${fiscalPeriodId}`, {
    method: 'PATCH',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}
```

### 4. Get Trial Balance

```javascript
async function getTrialBalance(fiscalYear, fiscalMonth) {
  // Get account balances for the period
  const balances = await getAccountBalances(fiscalYear, fiscalMonth);

  // Get account details
  const accounts = await fetch(
    '/api/v1/accounts?filter[isPostable]=true&filter[status]=active',
    { headers }
  );
  const accountsData = await accounts.json();

  // Build trial balance
  const trialBalance = balances.data.map(balance => {
    const account = accountsData.data.find(a => a.id === balance.attributes.accountId.toString());
    return {
      accountCode: account?.attributes.code,
      accountName: account?.attributes.name,
      debit: balance.attributes.periodDebits,
      credit: balance.attributes.periodCredits,
      closingBalance: balance.attributes.closingBalance
    };
  });

  const totals = trialBalance.reduce(
    (acc, row) => ({
      debit: acc.debit + row.debit,
      credit: acc.credit + row.credit
    }),
    { debit: 0, credit: 0 }
  );

  return {
    fiscalYear,
    fiscalMonth,
    accounts: trialBalance,
    totalDebit: totals.debit,
    totalCredit: totals.credit,
    balanced: Math.abs(totals.debit - totals.credit) < 0.01
  };
}
```

### 5. Reverse Journal Entry

```javascript
async function reverseJournalEntry(originalEntryId, reason) {
  // 1. Get original entry with lines
  const originalResponse = await fetch(
    `/api/v1/journal-entries/${originalEntryId}?include=journalLines`,
    { headers }
  );

  const original = await originalResponse.json();
  const originalLines = original.included.filter(
    inc => inc.type === 'journal-lines'
  );

  // 2. Create reversal entry
  const reversalPayload = {
    data: {
      type: "journal-entries",
      attributes: {
        journalId: original.data.attributes.journalId,
        fiscalPeriodId: original.data.attributes.fiscalPeriodId,
        number: `REV-${original.data.attributes.number}`,
        date: new Date().toISOString().split('T')[0],
        reference: original.data.attributes.reference,
        description: `Reversal of ${original.data.attributes.number}`,
        status: "draft",
        reversalOfId: parseInt(originalEntryId),
        reversalReason: reason
      }
    }
  };

  const reversalResponse = await fetch('/api/v1/journal-entries', {
    method: 'POST',
    headers,
    body: JSON.stringify(reversalPayload)
  });

  const reversal = await reversalResponse.json();
  const reversalId = reversal.data.id;

  // 3. Create reversed lines (swap debit/credit)
  for (const line of originalLines) {
    const linePayload = {
      data: {
        type: "journal-lines",
        attributes: {
          journalEntryId: parseInt(reversalId),
          accountId: line.attributes.accountId,
          contactId: line.attributes.contactId,
          debit: line.attributes.credit, // Swap
          credit: line.attributes.debit,  // Swap
          description: line.attributes.description,
          reference: line.attributes.reference
        }
      }
    };

    await fetch('/api/v1/journal-lines', {
      method: 'POST',
      headers,
      body: JSON.stringify(linePayload)
    });
  }

  return reversal;
}
```

---

## Permissions

### Role-Based Access

| Role | Accounts | Entries | Lines | Journals | Fiscal Periods | Exchange Rates | Audit Logs |
|------|----------|---------|-------|----------|----------------|----------------|------------|
| **God** | CRUD | CRUD | CRUD | CRUD | CRUD | CRUD | Read |
| **Admin** | CRUD | CRUD | CRUD | CRUD | CRUD | CRUD | Read |
| **Tech** | Read | Read | Read | Read | Read | Read | Read |
| **Customer** | - | - | - | - | - | - | - |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/accounts` - Chart of accounts
- `GET /api/v1/journals` - Journal types
- `GET /api/v1/journal-sequences` - Auto-numbering sequences
- `GET /api/v1/journal-entries` - List journal entries
- `GET /api/v1/journal-lines` - List journal lines
- `GET /api/v1/fiscal-periods` - Fiscal period management
- `GET /api/v1/exchange-rates` - Currency exchange rates
- `GET /api/v1/exchange-rate-policies` - Exchange rate policies
- `GET /api/v1/account-balances` - Account balance snapshots
- `GET /api/v1/account-mappings` - Account mappings configuration
- `GET /api/v1/audit-logs` - Audit trail (read-only)
- `GET /api/v1/idempotency-keys` - Request idempotency tracking

**Important Rules:**
- **Double-Entry:** Every journal entry must balance (totalDebit === totalCredit)
- **Posting:** Only posted entries affect account balances
- **Fiscal Periods:** Cannot post to closed or locked periods
- **Reversals:** Use reversalOfId to link reversal entries to originals
- **Audit:** All changes are logged automatically

**Related Modules:**
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - AR/AP invoices auto-post to GL
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Sales orders generate GL entries
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Purchase orders generate GL entries
- [HR Module](HR_FRONTEND_GUIDE.md) - Payroll posts to GL
- [Reports Module](REPORTS_FRONTEND_GUIDE.md) - Financial statements from GL
