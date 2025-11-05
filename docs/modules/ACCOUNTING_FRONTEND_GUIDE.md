# Accounting Module - Frontend Integration Guide

**Module:** Accounting
**Entities:** 7 (Account, JournalEntry, JournalLine, Journal, FiscalPeriod, ExchangeRate, AccountBalance)
**Endpoints:** 35
**Base Path:** `/api/v1`

## Overview

The Accounting module provides double-entry bookkeeping functionality including chart of accounts, journal entries, fiscal period management, and exchange rates. All financial transactions from other modules (Sales, Purchase, Finance, HR) automatically post to the general ledger.

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

### 2. JournalEntry

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
  totalDebit: number;
  totalCredit: number;
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
| `totalDebit` | `total_debit` | number | Yes | Yes | No |
| `totalCredit` | `total_credit` | number | Yes | Yes | No |
| `status` | `status` | string | Yes | Yes | Yes |
| `postedAt` | `posted_at` | datetime | No | Yes | No |

#### Relationships

- `journal` → Journal (belongsTo)
- `fiscalPeriod` → FiscalPeriod (belongsTo)
- `journalEntry` → JournalEntry (belongsTo) - For reversals
- `journalLines` → JournalLine[] (hasMany)

---

### 3. JournalLine

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

#### Relationships

- `journalEntry` → JournalEntry (belongsTo)
- `account` → Account (belongsTo)

---

### 4. FiscalPeriod

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

#### Relationships

- `journalEntries` → JournalEntry[] (hasMany)

---

### 5. ExchangeRate

**Endpoint:** `/exchange-rates`
**Resource Type:** `exchange-rates`

#### TypeScript Interface

```typescript
interface ExchangeRate {
  id: string;
  fromCurrency: string;
  toCurrency: string;
  rate: number;
  effectiveDate: string;
  source: string | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}
```

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
        totalDebit: totalDebit,
        totalCredit: totalCredit,
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

### 2. Get Account Balance

```javascript
async function getAccountBalance(accountId, fiscalPeriodId) {
  // Get all journal lines for the account in the fiscal period
  const response = await fetch(
    `/api/v1/journal-lines?filter[accountId]=${accountId}&include=journalEntry`,
    { headers }
  );

  const lines = await response.json();

  // Filter by fiscal period and posted status
  const periodLines = lines.data.filter(line => {
    const entry = lines.included.find(
      inc => inc.type === 'journal-entries' && inc.id === line.relationships.journalEntry.data.id
    );
    return entry &&
           entry.attributes.fiscalPeriodId === fiscalPeriodId &&
           entry.attributes.status === 'posted';
  });

  // Calculate balance
  const totalDebit = periodLines.reduce((sum, line) => sum + line.attributes.debit, 0);
  const totalCredit = periodLines.reduce((sum, line) => sum + line.attributes.credit, 0);

  return {
    accountId: accountId,
    fiscalPeriodId: fiscalPeriodId,
    totalDebit: totalDebit,
    totalCredit: totalCredit,
    balance: totalDebit - totalCredit // Positive for debit balance accounts
  };
}
```

### 3. Close Fiscal Period

```javascript
async function closeFiscalPeriod(fiscalPeriodId) {
  // 1. Verify all entries are posted
  const entriesResponse = await fetch(
    `/api/v1/journal-entries?filter[fiscalPeriodId]=${fiscalPeriodId}`,
    { headers }
  );

  const entries = await entriesResponse.json();
  const hasUnpostedEntries = entries.data.some(
    entry => entry.attributes.status !== 'posted'
  );

  if (hasUnpostedEntries) {
    throw new Error('Cannot close period: some entries are not posted');
  }

  // 2. Create closing entries (if needed)
  // ... (revenue and expense closing logic)

  // 3. Close the period
  const payload = {
    data: {
      type: "fiscal-periods",
      id: fiscalPeriodId,
      attributes: {
        status: "closed",
        closedAt: new Date().toISOString(),
        closedById: currentUser.id
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

### 4. Reverse Journal Entry

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
        totalDebit: original.data.attributes.totalCredit,
        totalCredit: original.data.attributes.totalDebit,
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

### 5. Get Trial Balance

```javascript
async function getTrialBalance(fiscalPeriodId) {
  // Get all accounts
  const accountsResponse = await fetch(
    '/api/v1/accounts?filter[isPostable]=true&filter[status]=active',
    { headers }
  );

  const accounts = await accountsResponse.json();

  // Calculate balance for each account
  const trialBalance = [];

  for (const account of accounts.data) {
    const balance = await getAccountBalance(
      account.id,
      fiscalPeriodId
    );

    if (balance.totalDebit !== 0 || balance.totalCredit !== 0) {
      trialBalance.push({
        accountCode: account.attributes.code,
        accountName: account.attributes.name,
        debit: balance.totalDebit,
        credit: balance.totalCredit
      });
    }
  }

  // Calculate totals
  const totals = trialBalance.reduce(
    (acc, row) => ({
      debit: acc.debit + row.debit,
      credit: acc.credit + row.credit
    }),
    { debit: 0, credit: 0 }
  );

  return {
    fiscalPeriodId: fiscalPeriodId,
    accounts: trialBalance,
    totalDebit: totals.debit,
    totalCredit: totals.credit,
    balanced: Math.abs(totals.debit - totals.credit) < 0.01
  };
}
```

---

## Permissions

### Role-Based Access

| Role | Accounts | Entries | Lines | Fiscal Periods | Exchange Rates |
|------|----------|---------|-------|----------------|----------------|
| **God** | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD |
| **Admin** | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD |
| **Tech** | ✅ Read | ✅ Read | ✅ Read | ✅ Read | ✅ Read |
| **Customer** | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/accounts` - Chart of accounts
- `GET /api/v1/journal-entries` - List journal entries
- `GET /api/v1/journal-lines` - List journal lines
- `GET /api/v1/fiscal-periods` - Fiscal period management
- `GET /api/v1/exchange-rates` - Currency exchange rates

**Important Rules:**
- **Double-Entry:** Every journal entry must balance (totalDebit === totalCredit)
- **Posting:** Only posted entries affect account balances
- **Fiscal Periods:** Cannot post to closed or locked periods
- **Reversals:** Use reversalOfId to link reversal entries to originals

**Related Modules:**
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - AR/AP invoices auto-post to GL
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Sales orders generate GL entries
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Purchase orders generate GL entries
- [HR Module](HR_FRONTEND_GUIDE.md) - Payroll posts to GL
- [Reports Module](REPORTS_FRONTEND_GUIDE.md) - Financial statements from GL
