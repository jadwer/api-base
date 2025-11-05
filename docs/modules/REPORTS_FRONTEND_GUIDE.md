# Reports Module - Frontend Integration Guide

**Module:** Reports
**Entities:** 10 (BalanceSheet, IncomeStatement, CashFlow, TrialBalance, ARAgingReport, APAgingReport, SalesByCustomer, SalesByProduct, PurchaseBySupplier, PurchaseByProduct)
**Endpoints:** 10 (Read-only)
**Base Path:** `/api/v1`

## Overview

The Reports module provides read-only financial and management reports. All reports are generated dynamically from data in the Accounting, Finance, Sales, and Purchase modules. Reports support date range filtering and currency selection.

**IMPORTANT:** All reports are **read-only**. They are virtual resources (no database tables) generated on-demand.

## Report Categories

### Financial Statements

#### 1. Balance Sheet

**Endpoint:** `GET /balance-sheets`
**Resource Type:** `balance-sheets`

#### TypeScript Interface

```typescript
interface BalanceSheet {
  id: string;
  asOfDate: string;
  currency: string;
  balanced: boolean;

  assets: {
    current: AccountLine[];
    nonCurrent: AccountLine[];
  };
  totalAssets: number;

  liabilities: {
    current: AccountLine[];
    nonCurrent: AccountLine[];
  };
  totalLiabilities: number;

  equity: AccountLine[];
  totalEquity: number;

  generatedAt: string;
}

interface AccountLine {
  code: string;
  name: string;
  amount: number;
}
```

#### Query Parameters

- `asOfDate` - Date for the report (default: today)
- `currency` - Currency code (default: USD)

#### Example

```javascript
const response = await fetch(
  '/api/v1/balance-sheets?filter[asOfDate]=2025-11-05&filter[currency]=USD',
  { headers }
);

const balanceSheet = await response.json();

console.log('Total Assets:', balanceSheet.data.attributes.totalAssets);
console.log('Total Liabilities:', balanceSheet.data.attributes.totalLiabilities);
console.log('Total Equity:', balanceSheet.data.attributes.totalEquity);
console.log('Balanced:', balanceSheet.data.attributes.balanced); // Assets = Liabilities + Equity
```

---

#### 2. Income Statement

**Endpoint:** `GET /income-statements`
**Resource Type:** `income-statements`

#### TypeScript Interface

```typescript
interface IncomeStatement {
  id: string;
  period: {
    startDate: string;
    endDate: string;
  };
  currency: string;

  revenue: AccountLine[];
  costOfGoodsSold: number;
  grossProfit: number;
  grossProfitMargin: number; // Percentage

  operatingExpenses: AccountLine[];
  operatingIncome: number;
  operatingMargin: number; // Percentage

  otherIncomeExpenses: AccountLine[];
  netIncome: number;
  netProfitMargin: number; // Percentage

  generatedAt: string;
}
```

#### Query Parameters

- `startDate` - Period start date (required)
- `endDate` - Period end date (required)
- `currency` - Currency code (default: USD)

#### Example

```javascript
const response = await fetch(
  '/api/v1/income-statements?filter[startDate]=2025-01-01&filter[endDate]=2025-11-05',
  { headers }
);

const incomeStatement = await response.json();

console.log('Gross Profit:', incomeStatement.data.attributes.grossProfit);
console.log('Gross Margin:', incomeStatement.data.attributes.grossProfitMargin + '%');
console.log('Net Income:', incomeStatement.data.attributes.netIncome);
console.log('Net Margin:', incomeStatement.data.attributes.netProfitMargin + '%');
```

---

#### 3. Cash Flow Statement

**Endpoint:** `GET /cash-flows`
**Resource Type:** `cash-flows`

#### TypeScript Interface

```typescript
interface CashFlow {
  id: string;
  period: {
    startDate: string;
    endDate: string;
  };
  currency: string;

  operatingActivities: AccountLine[];
  netCashFromOperations: number;

  investingActivities: AccountLine[];
  netCashFromInvesting: number;

  financingActivities: AccountLine[];
  netCashFromFinancing: number;

  netCashChange: number;
  beginningCash: number;
  endingCash: number;

  generatedAt: string;
}
```

---

#### 4. Trial Balance

**Endpoint:** `GET /trial-balances`
**Resource Type:** `trial-balances`

#### TypeScript Interface

```typescript
interface TrialBalance {
  id: string;
  asOfDate: string;
  currency: string;

  accounts: {
    code: string;
    name: string;
    debit: number;
    credit: number;
  }[];

  totalDebit: number;
  totalCredit: number;
  balanced: boolean; // totalDebit === totalCredit

  generatedAt: string;
}
```

---

### Aging Reports

#### 5. AR Aging Report

**Endpoint:** `GET /ar-aging-reports`
**Resource Type:** `ar-aging-reports`

#### TypeScript Interface

```typescript
interface ARAgingReport {
  id: string;
  asOfDate: string;
  currency: string;

  summary: {
    current: number;      // 0-30 days
    days30: number;       // 31-60 days
    days60: number;       // 61-90 days
    days90Plus: number;   // 90+ days
    total: number;
  };

  customers: {
    contactId: number;
    contactName: string;
    current: number;
    days30: number;
    days60: number;
    days90Plus: number;
    total: number;
  }[];

  generatedAt: string;
}
```

#### Example

```javascript
const response = await fetch(
  '/api/v1/ar-aging-reports?filter[asOfDate]=2025-11-05',
  { headers }
);

const aging = await response.json();

console.log('Current (0-30 days):', aging.data.attributes.summary.current);
console.log('30-60 days:', aging.data.attributes.summary.days30);
console.log('60-90 days:', aging.data.attributes.summary.days60);
console.log('90+ days (overdue):', aging.data.attributes.summary.days90Plus);
console.log('Total Receivables:', aging.data.attributes.summary.total);
```

---

#### 6. AP Aging Report

**Endpoint:** `GET /ap-aging-reports`
**Resource Type:** `ap-aging-reports`

Same structure as AR Aging Report, but for accounts payable (amounts owed to suppliers).

---

### Management Reports

#### 7. Sales by Customer

**Endpoint:** `GET /sales-by-customer-reports`
**Resource Type:** `sales-by-customer-reports`

#### TypeScript Interface

```typescript
interface SalesByCustomer {
  id: string;
  period: {
    startDate: string;
    endDate: string;
  };
  currency: string;

  customers: {
    contactId: number;
    contactName: string;
    orderCount: number;
    totalSales: number;
    averageOrderValue: number;
  }[];

  totalSales: number;
  generatedAt: string;
}
```

---

#### 8. Sales by Product

**Endpoint:** `GET /sales-by-product-reports`
**Resource Type:** `sales-by-product-reports`

#### TypeScript Interface

```typescript
interface SalesByProduct {
  id: string;
  period: {
    startDate: string;
    endDate: string;
  };
  currency: string;

  products: {
    productId: number;
    productName: string;
    quantitySold: number;
    totalRevenue: number;
    averagePrice: number;
  }[];

  totalRevenue: number;
  generatedAt: string;
}
```

---

#### 9. Purchase by Supplier

**Endpoint:** `GET /purchase-by-supplier-reports`
**Resource Type:** `purchase-by-supplier-reports`

Similar to Sales by Customer but for purchases from suppliers.

---

#### 10. Purchase by Product

**Endpoint:** `GET /purchase-by-product-reports`
**Resource Type:** `purchase-by-product-reports`

Similar to Sales by Product but for purchased items.

---

## Common Use Cases

### 1. Generate Financial Dashboard

```javascript
async function getFinancialDashboard(startDate, endDate) {
  // Fetch multiple reports in parallel
  const [balanceSheet, incomeStatement, cashFlow, arAging] = await Promise.all([
    fetch(`/api/v1/balance-sheets?filter[asOfDate]=${endDate}`, { headers }),
    fetch(`/api/v1/income-statements?filter[startDate]=${startDate}&filter[endDate]=${endDate}`, { headers }),
    fetch(`/api/v1/cash-flows?filter[startDate]=${startDate}&filter[endDate]=${endDate}`, { headers }),
    fetch(`/api/v1/ar-aging-reports?filter[asOfDate]=${endDate}`, { headers })
  ]);

  const [bs, is, cf, ar] = await Promise.all([
    balanceSheet.json(),
    incomeStatement.json(),
    cashFlow.json(),
    arAging.json()
  ]);

  return {
    balance: {
      assets: bs.data.attributes.totalAssets,
      liabilities: bs.data.attributes.totalLiabilities,
      equity: bs.data.attributes.totalEquity
    },
    performance: {
      revenue: is.data.attributes.revenue.reduce((sum, line) => sum + line.amount, 0),
      netIncome: is.data.attributes.netIncome,
      netMargin: is.data.attributes.netProfitMargin
    },
    cash: {
      endingBalance: cf.data.attributes.endingCash,
      netChange: cf.data.attributes.netCashChange
    },
    receivables: {
      total: ar.data.attributes.summary.total,
      overdue: ar.data.attributes.summary.days90Plus
    }
  };
}
```

### 2. Export Report to CSV

```javascript
async function exportBalanceSheetToCsv(asOfDate) {
  const response = await fetch(
    `/api/v1/balance-sheets?filter[asOfDate]=${asOfDate}`,
    { headers }
  );

  const bs = await response.json();

  // Convert to CSV
  let csv = 'Account Code,Account Name,Amount\n';

  // Assets
  csv += 'ASSETS\n';
  bs.data.attributes.assets.current.forEach(line => {
    csv += `${line.code},${line.name},${line.amount}\n`;
  });
  bs.data.attributes.assets.nonCurrent.forEach(line => {
    csv += `${line.code},${line.name},${line.amount}\n`;
  });
  csv += `,,${bs.data.attributes.totalAssets}\n\n`;

  // Liabilities
  csv += 'LIABILITIES\n';
  bs.data.attributes.liabilities.current.forEach(line => {
    csv += `${line.code},${line.name},${line.amount}\n`;
  });
  bs.data.attributes.liabilities.nonCurrent.forEach(line => {
    csv += `${line.code},${line.name},${line.amount}\n`;
  });
  csv += `,,${bs.data.attributes.totalLiabilities}\n\n`;

  // Equity
  csv += 'EQUITY\n';
  bs.data.attributes.equity.forEach(line => {
    csv += `${line.code},${line.name},${line.amount}\n`;
  });
  csv += `,,${bs.data.attributes.totalEquity}\n`;

  // Download
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `balance-sheet-${asOfDate}.csv`;
  a.click();
}
```

### 3. Compare Periods

```javascript
async function compareIncomeStatements(period1, period2) {
  const [is1, is2] = await Promise.all([
    fetch(`/api/v1/income-statements?filter[startDate]=${period1.start}&filter[endDate]=${period1.end}`, { headers }),
    fetch(`/api/v1/income-statements?filter[startDate]=${period2.start}&filter[endDate]=${period2.end}`, { headers })
  ]);

  const [data1, data2] = await Promise.all([is1.json(), is2.json()]);

  return {
    netIncome: {
      period1: data1.data.attributes.netIncome,
      period2: data2.data.attributes.netIncome,
      change: data2.data.attributes.netIncome - data1.data.attributes.netIncome,
      changePercent: ((data2.data.attributes.netIncome - data1.data.attributes.netIncome) /
                     data1.data.attributes.netIncome * 100).toFixed(2)
    },
    revenue: {
      period1: data1.data.attributes.revenue.reduce((sum, l) => sum + l.amount, 0),
      period2: data2.data.attributes.revenue.reduce((sum, l) => sum + l.amount, 0)
    }
  };
}
```

---

## Permissions

### Role-Based Access

| Role | Financial Statements | Aging Reports | Management Reports |
|------|---------------------|---------------|--------------------|
| **God** | ✅ Read | ✅ Read | ✅ Read |
| **Admin** | ✅ Read | ✅ Read | ✅ Read |
| **Tech** | ✅ Read | ✅ Read | ✅ Read |
| **Customer** | ❌ | ❌ | ❌ |

---

## Quick Reference

**Financial Statements:**
- `GET /api/v1/balance-sheets` - Balance sheet
- `GET /api/v1/income-statements` - Income statement
- `GET /api/v1/cash-flows` - Cash flow statement
- `GET /api/v1/trial-balances` - Trial balance

**Aging Reports:**
- `GET /api/v1/ar-aging-reports` - Accounts receivable aging
- `GET /api/v1/ap-aging-reports` - Accounts payable aging

**Management Reports:**
- `GET /api/v1/sales-by-customer-reports` - Customer sales analysis
- `GET /api/v1/sales-by-product-reports` - Product sales analysis
- `GET /api/v1/purchase-by-supplier-reports` - Supplier purchase analysis
- `GET /api/v1/purchase-by-product-reports` - Product purchase analysis

**Important Notes:**
- All reports are **read-only** (GET only)
- All reports are **generated on-demand** (no caching)
- Use date filters to narrow results: `filter[startDate]`, `filter[endDate]`, `filter[asOfDate]`
- All amounts respect the selected currency

**Related Modules:**
- [Accounting Module](ACCOUNTING_FRONTEND_GUIDE.md) - Source data for financial statements
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - Source data for aging reports
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Source data for sales reports
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Source data for purchase reports
