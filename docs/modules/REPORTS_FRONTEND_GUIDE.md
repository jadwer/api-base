# Reports Module - Frontend Integration Guide

**Module:** Reports
**Entities:** 10 virtual reports
**Endpoints:** 20 (Read-only)
**Base Path:** `/api/v1/reports`
**Updated:** 2025-12-24

## Overview

The Reports module provides read-only financial and management reports. All reports are generated dynamically from data in the Accounting, Finance, Sales, and Purchase modules.

**Key Characteristics:**
- All reports are **read-only** (GET only)
- Reports use **JSON:API format** with `jsonapi`, `data`, and `attributes`
- They are virtual resources (no database tables) generated on-demand
- Base path is `/api/v1/reports/`
- Default currency is **MXN**

---

## Financial Statements

### 1. Balance Sheet (Balance General)

**Endpoints:**
- `GET /api/v1/reports/balance-sheets` - Lista
- `GET /api/v1/reports/balance-sheets/{id}` - Individual

#### TypeScript Interface

```typescript
interface BalanceSheetResponse {
  jsonapi: { version: '1.0' };
  data: {
    type: 'balance-sheets';
    id: string;
    attributes: {
      asOfDate: string;
      currency: string;
      balanced: boolean;
      assets: CategoryGroup[];
      totalAssets: number;
      liabilities: CategoryGroup[];
      totalLiabilities: number;
      equity: CategoryGroup[];
      totalEquity: number;
      generatedAt: string;
    };
  }[];
}

interface CategoryGroup {
  category: string;
  accounts: AccountLine[];
  subtotal: number;
}

interface AccountLine {
  code: string;
  name: string;
  account_type: string;
  balance: number;
}
```

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `asOfDate` | `2025-12-24` | Date for the report (default: today) |
| `filter.asOfDate` | `2025-12-24` | Alternative filter format |
| `currency` | `MXN` | Currency code (default: MXN) |
| `filter.currency` | `USD` | Alternative filter format |

#### Example

```javascript
const response = await fetch(
  '/api/v1/reports/balance-sheets?asOfDate=2025-12-24&currency=MXN',
  { headers: { Authorization: `Bearer ${token}` } }
);

const result = await response.json();
const balanceSheet = result.data[0].attributes;

console.log('Total Assets:', balanceSheet.totalAssets);
console.log('Total Liabilities:', balanceSheet.totalLiabilities);
console.log('Total Equity:', balanceSheet.totalEquity);
console.log('Balanced:', balanceSheet.balanced);
```

---

### 2. Income Statement (Estado de Resultados)

**Endpoints:**
- `GET /api/v1/reports/income-statements` - Lista
- `GET /api/v1/reports/income-statements/{id}` - Individual

#### TypeScript Interface

```typescript
interface IncomeStatementResponse {
  jsonapi: { version: '1.0' };
  data: {
    type: 'income-statements';
    id: string;
    attributes: {
      startDate: string;
      endDate: string;
      currency: string;
      revenues: CategoryGroup[];
      totalRevenues: number;
      expenses: CategoryGroup[];
      totalExpenses: number;
      netIncome: number;
      generatedAt: string;
    };
  }[];
}
```

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `startDate` | `2025-01-01` | Period start (default: first of month) |
| `endDate` | `2025-12-24` | Period end (default: today) |
| `currency` | `MXN` | Currency code (default: MXN) |

#### Example

```javascript
const response = await fetch(
  '/api/v1/reports/income-statements?startDate=2025-01-01&endDate=2025-12-24',
  { headers: { Authorization: `Bearer ${token}` } }
);

const result = await response.json();
const income = result.data[0].attributes;

console.log('Total Revenues:', income.totalRevenues);
console.log('Total Expenses:', income.totalExpenses);
console.log('Net Income:', income.netIncome);
```

---

### 3. Cash Flow Statement (Flujo de Efectivo)

**Endpoints:**
- `GET /api/v1/reports/cash-flows` - Lista
- `GET /api/v1/reports/cash-flows/{id}` - Individual

#### TypeScript Interface

```typescript
interface CashFlowResponse {
  jsonapi: { version: '1.0' };
  data: {
    type: 'cash-flows';
    id: string;
    attributes: {
      startDate: string;
      endDate: string;
      currency: string;
      beginningCash: number;
      operatingActivities: number;
      investingActivities: number;
      financingActivities: number;
      netCashFlow: number;
      endingCash: number;
      generatedAt: string;
    };
  }[];
}
```

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `startDate` | `2025-01-01` | Period start (default: first of month) |
| `endDate` | `2025-12-24` | Period end (default: today) |
| `currency` | `MXN` | Currency code (default: MXN) |

---

### 4. Trial Balance (Balanza de Comprobacion)

**Endpoints:**
- `GET /api/v1/reports/trial-balances` - Lista
- `GET /api/v1/reports/trial-balances/{id}` - Individual

#### TypeScript Interface

```typescript
interface TrialBalanceResponse {
  jsonapi: { version: '1.0' };
  data: {
    type: 'trial-balances';
    id: string;
    attributes: {
      asOfDate: string;
      currency: string;
      accounts: TrialBalanceAccount[];
      totals: {
        debit: number;
        credit: number;
      };
      summaryByType: {
        type: string;
        totalDebit: number;
        totalCredit: number;
        count: number;
      }[];
      balanced: boolean;
      generatedAt: string;
    };
  }[];
}

interface TrialBalanceAccount {
  code: string;
  name: string;
  type: string;
  debit: number;
  credit: number;
}
```

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `asOfDate` | `2025-12-24` | Date for the report (default: today) |
| `currency` | `MXN` | Currency code (default: MXN) |

---

## Aging Reports

### 5. AR Aging Report (Antiguedad de Cuentas por Cobrar)

**Endpoints:**
- `GET /api/v1/reports/ar-aging-reports` - Lista
- `GET /api/v1/reports/ar-aging-reports/{id}` - Individual

#### TypeScript Interface

```typescript
interface ARAgingResponse {
  jsonapi: { version: '1.0' };
  data: {
    type: 'ar-aging-reports';
    id: string;
    attributes: {
      asOfDate: string;
      currency: string;
      agingBuckets: AgingBucket[];
      totals: AgingTotals;
      generatedAt: string;
    };
  }[];
}

interface AgingBucket {
  customerId: number | null;
  customerName: string;
  current: number;
  days1To30: number;
  days31To60: number;
  days61To90: number;
  daysOver90: number;
  total: number;
}

interface AgingTotals {
  current: number;
  days1To30: number;
  days31To60: number;
  days61To90: number;
  daysOver90: number;
  total: number;
}
```

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `asOfDate` | `2025-12-24` | Date for aging calculation |
| `currency` | `MXN` | Currency code |

#### Example

```javascript
const response = await fetch(
  '/api/v1/reports/ar-aging-reports?asOfDate=2025-12-24',
  { headers: { Authorization: `Bearer ${token}` } }
);

const result = await response.json();
const aging = result.data[0].attributes;

console.log('Current:', aging.totals.current);
console.log('1-30 days:', aging.totals.days1To30);
console.log('31-60 days:', aging.totals.days31To60);
console.log('61-90 days:', aging.totals.days61To90);
console.log('90+ days (overdue):', aging.totals.daysOver90);
console.log('Total Receivables:', aging.totals.total);
```

---

### 6. AP Aging Report (Antiguedad de Cuentas por Pagar)

**Endpoints:**
- `GET /api/v1/reports/ap-aging-reports` - Lista
- `GET /api/v1/reports/ap-aging-reports/{id}` - Individual

Same structure as AR Aging Report, but for accounts payable (amounts owed to suppliers).

---

## Management Reports

### 7. Sales by Customer (Ventas por Cliente)

**Endpoints:**
- `GET /api/v1/reports/sales-by-customer-reports` - Lista
- `GET /api/v1/reports/sales-by-customer-reports/{id}` - Individual

#### TypeScript Interface

```typescript
interface SalesByCustomerResponse {
  jsonapi: { version: '1.0' };
  data: {
    type: 'sales-by-customer-reports';
    id: string;
    attributes: {
      startDate: string;
      endDate: string;
      currency: string;
      salesByCustomer: CustomerSales[];
      summary: {
        totalCustomers: number;
        totalOrders: number;
        totalSales: number;
      };
      generatedAt: string;
    };
  }[];
}

interface CustomerSales {
  customerId: number | null;
  customerName: string;
  orderCount: number;
  totalSales: number;
}
```

#### Query Parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `startDate` | `2025-01-01` | Period start |
| `endDate` | `2025-12-24` | Period end |
| `currency` | `MXN` | Currency code |

---

### 8. Sales by Product (Ventas por Producto)

**Endpoints:**
- `GET /api/v1/reports/sales-by-product-reports` - Lista
- `GET /api/v1/reports/sales-by-product-reports/{id}` - Individual

#### TypeScript Interface

```typescript
interface SalesByProductResponse {
  jsonapi: { version: '1.0' };
  data: {
    type: 'sales-by-product-reports';
    id: string;
    attributes: {
      startDate: string;
      endDate: string;
      currency: string;
      salesByProduct: ProductSales[];
      summary: {
        totalProducts: number;
        totalQuantity: number;
        totalRevenue: number;
      };
      generatedAt: string;
    };
  }[];
}

interface ProductSales {
  productId: number;
  productName: string;
  quantitySold: number;
  totalRevenue: number;
}
```

---

### 9. Purchase by Supplier (Compras por Proveedor)

**Endpoints:**
- `GET /api/v1/reports/purchase-by-supplier-reports` - Lista
- `GET /api/v1/reports/purchase-by-supplier-reports/{id}` - Individual

Similar structure to Sales by Customer but for purchases from suppliers.

---

### 10. Purchase by Product (Compras por Producto)

**Endpoints:**
- `GET /api/v1/reports/purchase-by-product-reports` - Lista
- `GET /api/v1/reports/purchase-by-product-reports/{id}` - Individual

Similar structure to Sales by Product but for purchased items.

---

## Common Use Cases

### 1. Generate Financial Dashboard

```javascript
async function getFinancialDashboard(startDate, endDate) {
  const headers = { Authorization: `Bearer ${token}` };

  // Fetch multiple reports in parallel
  const [balanceSheet, incomeStatement, cashFlow, arAging] = await Promise.all([
    fetch(`/api/v1/reports/balance-sheets?asOfDate=${endDate}`, { headers }),
    fetch(`/api/v1/reports/income-statements?startDate=${startDate}&endDate=${endDate}`, { headers }),
    fetch(`/api/v1/reports/cash-flows?startDate=${startDate}&endDate=${endDate}`, { headers }),
    fetch(`/api/v1/reports/ar-aging-reports?asOfDate=${endDate}`, { headers })
  ]);

  const [bs, is, cf, ar] = await Promise.all([
    balanceSheet.json(),
    incomeStatement.json(),
    cashFlow.json(),
    arAging.json()
  ]);

  return {
    balance: {
      assets: bs.data[0].attributes.totalAssets,
      liabilities: bs.data[0].attributes.totalLiabilities,
      equity: bs.data[0].attributes.totalEquity
    },
    performance: {
      revenue: is.data[0].attributes.totalRevenues,
      expenses: is.data[0].attributes.totalExpenses,
      netIncome: is.data[0].attributes.netIncome
    },
    cash: {
      beginningCash: cf.data[0].attributes.beginningCash,
      endingCash: cf.data[0].attributes.endingCash,
      netCashFlow: cf.data[0].attributes.netCashFlow
    },
    receivables: {
      total: ar.data[0].attributes.totals.total,
      overdue: ar.data[0].attributes.totals.daysOver90
    }
  };
}
```

### 2. Export Report to CSV

```javascript
async function exportTrialBalanceToCsv(asOfDate) {
  const response = await fetch(
    `/api/v1/reports/trial-balances?asOfDate=${asOfDate}`,
    { headers: { Authorization: `Bearer ${token}` } }
  );

  const result = await response.json();
  const tb = result.data[0].attributes;

  // Convert to CSV
  let csv = 'Account Code,Account Name,Type,Debit,Credit\n';

  tb.accounts.forEach(account => {
    csv += `${account.code},${account.name},${account.type},${account.debit},${account.credit}\n`;
  });

  csv += `\nTOTALS,,,${tb.totals.debit},${tb.totals.credit}\n`;
  csv += `Balanced:,${tb.balanced}\n`;

  // Download
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `trial-balance-${asOfDate}.csv`;
  a.click();
}
```

---

## TypeScript Service

```typescript
class ReportsService {
  private baseUrl = '/api/v1/reports';
  private token: string;

  constructor(token: string) {
    this.token = token;
  }

  private get headers() {
    return {
      Authorization: `Bearer ${this.token}`,
      Accept: 'application/json',
    };
  }

  // Financial Statements
  async getBalanceSheet(asOfDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (asOfDate) params.set('asOfDate', asOfDate);
    const response = await fetch(`${this.baseUrl}/balance-sheets?${params}`, { headers: this.headers });
    return response.json();
  }

  async getIncomeStatement(startDate?: string, endDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (startDate) params.set('startDate', startDate);
    if (endDate) params.set('endDate', endDate);
    const response = await fetch(`${this.baseUrl}/income-statements?${params}`, { headers: this.headers });
    return response.json();
  }

  async getCashFlow(startDate?: string, endDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (startDate) params.set('startDate', startDate);
    if (endDate) params.set('endDate', endDate);
    const response = await fetch(`${this.baseUrl}/cash-flows?${params}`, { headers: this.headers });
    return response.json();
  }

  async getTrialBalance(asOfDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (asOfDate) params.set('asOfDate', asOfDate);
    const response = await fetch(`${this.baseUrl}/trial-balances?${params}`, { headers: this.headers });
    return response.json();
  }

  // Aging Reports
  async getARAgingReport(asOfDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (asOfDate) params.set('asOfDate', asOfDate);
    const response = await fetch(`${this.baseUrl}/ar-aging-reports?${params}`, { headers: this.headers });
    return response.json();
  }

  async getAPAgingReport(asOfDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (asOfDate) params.set('asOfDate', asOfDate);
    const response = await fetch(`${this.baseUrl}/ap-aging-reports?${params}`, { headers: this.headers });
    return response.json();
  }

  // Management Reports
  async getSalesByCustomer(startDate?: string, endDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (startDate) params.set('startDate', startDate);
    if (endDate) params.set('endDate', endDate);
    const response = await fetch(`${this.baseUrl}/sales-by-customer-reports?${params}`, { headers: this.headers });
    return response.json();
  }

  async getSalesByProduct(startDate?: string, endDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (startDate) params.set('startDate', startDate);
    if (endDate) params.set('endDate', endDate);
    const response = await fetch(`${this.baseUrl}/sales-by-product-reports?${params}`, { headers: this.headers });
    return response.json();
  }

  async getPurchaseBySupplier(startDate?: string, endDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (startDate) params.set('startDate', startDate);
    if (endDate) params.set('endDate', endDate);
    const response = await fetch(`${this.baseUrl}/purchase-by-supplier-reports?${params}`, { headers: this.headers });
    return response.json();
  }

  async getPurchaseByProduct(startDate?: string, endDate?: string, currency = 'MXN') {
    const params = new URLSearchParams({ currency });
    if (startDate) params.set('startDate', startDate);
    if (endDate) params.set('endDate', endDate);
    const response = await fetch(`${this.baseUrl}/purchase-by-product-reports?${params}`, { headers: this.headers });
    return response.json();
  }
}
```

---

## Permissions

| Permission | Description | Roles |
|------------|-------------|-------|
| `reports.balance-sheets.index` | List balance sheets | god, admin, tech |
| `reports.balance-sheets.show` | View balance sheet | god, admin, tech |
| `reports.income-statements.index` | List income statements | god, admin, tech |
| `reports.income-statements.show` | View income statement | god, admin, tech |
| `reports.cash-flows.index` | List cash flows | god, admin, tech |
| `reports.cash-flows.show` | View cash flow | god, admin, tech |
| `reports.trial-balances.index` | List trial balances | god, admin, tech |
| `reports.trial-balances.show` | View trial balance | god, admin, tech |
| `reports.ar-aging-reports.index` | List AR aging | god, admin, tech |
| `reports.ar-aging-reports.show` | View AR aging | god, admin, tech |
| `reports.ap-aging-reports.index` | List AP aging | god, admin, tech |
| `reports.ap-aging-reports.show` | View AP aging | god, admin, tech |
| `reports.sales-by-customer-reports.index` | List sales by customer | god, admin, tech |
| `reports.sales-by-customer-reports.show` | View sales by customer | god, admin, tech |
| `reports.sales-by-product-reports.index` | List sales by product | god, admin, tech |
| `reports.sales-by-product-reports.show` | View sales by product | god, admin, tech |
| `reports.purchase-by-supplier-reports.index` | List purchase by supplier | god, admin, tech |
| `reports.purchase-by-supplier-reports.show` | View purchase by supplier | god, admin, tech |
| `reports.purchase-by-product-reports.index` | List purchase by product | god, admin, tech |
| `reports.purchase-by-product-reports.show` | View purchase by product | god, admin, tech |

---

## Quick Reference

| Report | Endpoint | Parameters |
|--------|----------|------------|
| Balance Sheet | `GET /api/v1/reports/balance-sheets` | asOfDate, currency |
| Income Statement | `GET /api/v1/reports/income-statements` | startDate, endDate, currency |
| Cash Flow | `GET /api/v1/reports/cash-flows` | startDate, endDate, currency |
| Trial Balance | `GET /api/v1/reports/trial-balances` | asOfDate, currency |
| AR Aging | `GET /api/v1/reports/ar-aging-reports` | asOfDate, currency |
| AP Aging | `GET /api/v1/reports/ap-aging-reports` | asOfDate, currency |
| Sales by Customer | `GET /api/v1/reports/sales-by-customer-reports` | startDate, endDate, currency |
| Sales by Product | `GET /api/v1/reports/sales-by-product-reports` | startDate, endDate, currency |
| Purchase by Supplier | `GET /api/v1/reports/purchase-by-supplier-reports` | startDate, endDate, currency |
| Purchase by Product | `GET /api/v1/reports/purchase-by-product-reports` | startDate, endDate, currency |

---

## Related Modules

- [Accounting Module](ACCOUNTING_FRONTEND_GUIDE.md) - Source data for financial statements
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - Source data for aging reports
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Source data for sales reports
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Source data for purchase reports
