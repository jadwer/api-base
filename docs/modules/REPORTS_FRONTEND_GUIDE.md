# Reports Module - Frontend Integration Guide

**Module:** Reports
**Entities:** 14 (BalanceSheet, IncomeStatement, CashFlow, TrialBalance, ARAgingReport, APAgingReport, SalesByCustomer, SalesByProduct, PurchaseBySupplier, PurchaseByProduct)
**Endpoints:** 17 (Read-only)
**Base Path:** `/api/v1/reports`

> **Updated 2025-12-16:** Endpoints corregidos. Los endpoints NO son JSON:API, retornan JSON simple.

## Overview

The Reports module provides read-only financial and management reports. All reports are generated dynamically from data in the Accounting, Finance, Sales, and Purchase modules. Reports support date range filtering and currency selection.

**IMPORTANT:**
- All reports are **read-only** (GET only)
- Reports are **NOT JSON:API compliant** - they return simple JSON
- They are virtual resources (no database tables) generated on-demand
- Base path is `/api/v1/reports/` (not `/api/v1/`)

## Report Categories

### Financial Statements

#### 1. Balance Sheet (Balance General)

**Endpoints:**
- `GET /api/v1/reports/balance-sheet` - Balance General
- `GET /api/v1/reports/balance-sheet/comparative` - Balance Comparativo

#### TypeScript Interface

```typescript
interface BalanceSheetResponse {
  data: {
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
  meta: {
    reportType: string;
    asOfDate: string;
    currency: string;
    generatedAt: string;
  };
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

- `asOfDate` or `filter[asOfDate]` - Date for the report (default: today)
- `currency` or `filter[currency]` - Currency code (default: MXN)

#### Example

```javascript
const response = await fetch(
  '/api/v1/reports/balance-sheet?asOfDate=2025-12-16&currency=MXN',
  { headers: { Authorization: `Bearer ${token}` } }
);

const balanceSheet = await response.json();

// Note: Response is NOT JSON:API format
console.log('Total Assets:', balanceSheet.data.totalAssets);
console.log('Total Liabilities:', balanceSheet.data.totalLiabilities);
console.log('Total Equity:', balanceSheet.data.totalEquity);
console.log('Balanced:', balanceSheet.data.balanced);
```

---

#### 2. Income Statement (Estado de Resultados)

**Endpoints:**
- `GET /api/v1/reports/income-statement` - Estado de Resultados
- `GET /api/v1/reports/income-statement/comparative` - Comparativo entre periodos

#### TypeScript Interface

```typescript
interface IncomeStatementResponse {
  data: {
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
  meta: {
    reportType: string;
    startDate: string;
    endDate: string;
    currency: string;
    generatedAt: string;
  };
}
```

#### Query Parameters

- `startDate` or `filter[startDate]` - Period start date (default: first of month)
- `endDate` or `filter[endDate]` - Period end date (default: today)
- `currency` or `filter[currency]` - Currency code (default: MXN)

#### Example

```javascript
const response = await fetch(
  '/api/v1/reports/income-statement?startDate=2025-01-01&endDate=2025-12-16',
  { headers: { Authorization: `Bearer ${token}` } }
);

const incomeStatement = await response.json();

console.log('Total Revenues:', incomeStatement.data.totalRevenues);
console.log('Total Expenses:', incomeStatement.data.totalExpenses);
console.log('Net Income:', incomeStatement.data.netIncome);
```

---

#### 3. Cash Flow Statement (Flujo de Efectivo)

**Endpoints:**
- `GET /api/v1/reports/cash-flow` - Flujo de Efectivo
- `GET /api/v1/reports/cash-flow/comparative` - Comparativo entre periodos

#### TypeScript Interface

```typescript
interface CashFlowResponse {
  data: {
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
  meta: {
    reportType: string;
    startDate: string;
    endDate: string;
    currency: string;
    generatedAt: string;
  };
}
```

#### Query Parameters

- `startDate` or `filter[startDate]` - Period start date (default: first of month)
- `endDate` or `filter[endDate]` - Period end date (default: today)
- `currency` or `filter[currency]` - Currency code (default: MXN)

---

#### 4. Trial Balance (Balanza de Comprobacion)

**Endpoints:**
- `GET /api/v1/reports/trial-balance` - Balanza de Comprobacion
- `GET /api/v1/reports/trial-balance/comparative` - Comparativa
- `GET /api/v1/reports/trial-balance/detailed` - Con desglose de movimientos

#### TypeScript Interface

```typescript
interface TrialBalanceResponse {
  data: {
    asOfDate: string;
    currency: string;
    accounts: {
      code: string;
      name: string;
      type: string;
      debit: number;
      credit: number;
    }[];
    totals: {
      debit: number;
      credit: number;
    };
    balanced: boolean;
    summaryByType: {
      type: string;
      totalDebit: number;
      totalCredit: number;
      count: number;
    }[];
    generatedAt: string;
  };
  meta: {
    reportType: string;
    asOfDate: string;
    currency: string;
    generatedAt: string;
  };
}
```

#### Query Parameters

- `asOfDate` or `filter[asOfDate]` - Date for the report (default: today)
- `currency` or `filter[currency]` - Currency code (default: MXN)

---

### Aging Reports

#### 5. AR Aging Report (Antiguedad de Cuentas por Cobrar)

**Endpoint:** `GET /api/v1/reports/aging-ar`

#### TypeScript Interface

```typescript
interface ARAgingResponse {
  data: {
    as_of_date: string;
    report_type: string;
    currency: string;
    customers: {
      customer_id: number | null;
      customer_name: string;
      current: number;
      days_1_30: number;
      days_31_60: number;
      days_61_90: number;
      days_over_90: number;
      total: number;
    }[];
    summary: {
      current: number;
      days_1_30: number;
      days_31_60: number;
      days_61_90: number;
      days_over_90: number;
      total: number;
    };
  };
}
```

#### Example

```javascript
const response = await fetch(
  '/api/v1/reports/aging-ar',
  { headers: { Authorization: `Bearer ${token}` } }
);

const aging = await response.json();

console.log('Current:', aging.data.summary.current);
console.log('1-30 days:', aging.data.summary.days_1_30);
console.log('31-60 days:', aging.data.summary.days_31_60);
console.log('61-90 days:', aging.data.summary.days_61_90);
console.log('90+ days (overdue):', aging.data.summary.days_over_90);
console.log('Total Receivables:', aging.data.summary.total);
```

---

#### 6. AP Aging Report (Antiguedad de Cuentas por Pagar)

**Endpoint:** `GET /api/v1/reports/aging-ap`

Same structure as AR Aging Report, but for accounts payable (amounts owed to suppliers).

---

### Management Reports

#### 7. Sales by Customer (Ventas por Cliente)

**Endpoint:** `GET /api/v1/reports/sales-by-customer`

#### TypeScript Interface

```typescript
interface SalesByCustomerResponse {
  data: {
    period: {
      start_date: string;
      end_date: string;
    };
    report_type: string;
    currency: string;
    customers: {
      customer_id: number | null;
      customer_name: string;
      order_count: number;
      total_sales: number;
    }[];
    summary: {
      total_customers: number;
      total_orders: number;
      total_sales: number;
    };
  };
}
```

---

#### 8. Sales by Product (Ventas por Producto)

**Endpoint:** `GET /api/v1/reports/sales-by-product`

#### TypeScript Interface

```typescript
interface SalesByProductResponse {
  data: {
    period: {
      start_date: string;
      end_date: string;
    };
    report_type: string;
    currency: string;
    products: {
      product_id: number;
      product_name: string;
      quantity_sold: number;
      total_revenue: number;
    }[];
    summary: {
      total_products: number;
      total_quantity: number;
      total_revenue: number;
    };
  };
}
```

---

#### 9. Purchase by Supplier (Compras por Proveedor)

**Endpoint:** `GET /api/v1/reports/purchase-by-supplier`

Similar to Sales by Customer but for purchases from suppliers.

---

#### 10. Purchase by Product (Compras por Producto)

**Endpoint:** `GET /api/v1/reports/purchase-by-product`

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

> **Note:** All endpoints use base path `/api/v1/reports/` and return simple JSON (NOT JSON:API format)

**Financial Statements:**
| Endpoint | Description |
|----------|-------------|
| `GET /api/v1/reports/balance-sheet` | Balance General |
| `GET /api/v1/reports/balance-sheet/comparative` | Balance Comparativo |
| `GET /api/v1/reports/income-statement` | Estado de Resultados |
| `GET /api/v1/reports/income-statement/comparative` | Resultados Comparativo |
| `GET /api/v1/reports/cash-flow` | Flujo de Efectivo |
| `GET /api/v1/reports/cash-flow/comparative` | Flujo Comparativo |
| `GET /api/v1/reports/trial-balance` | Balanza de Comprobacion |
| `GET /api/v1/reports/trial-balance/comparative` | Balanza Comparativa |
| `GET /api/v1/reports/trial-balance/detailed` | Balanza Detallada |

**Aging Reports:**
| Endpoint | Description |
|----------|-------------|
| `GET /api/v1/reports/aging-ar` | Antiguedad Cuentas por Cobrar |
| `GET /api/v1/reports/aging-ap` | Antiguedad Cuentas por Pagar |

**Management Reports:**
| Endpoint | Description |
|----------|-------------|
| `GET /api/v1/reports/sales-by-customer` | Ventas por Cliente |
| `GET /api/v1/reports/sales-by-product` | Ventas por Producto |
| `GET /api/v1/reports/purchase-by-supplier` | Compras por Proveedor |
| `GET /api/v1/reports/purchase-by-product` | Compras por Producto |

**Analytics (⚠️ Require fixes - avoid in production):**
| Endpoint | Status |
|----------|--------|
| `GET /api/v1/analytics/dashboard` | ❌ Error |
| `GET /api/v1/analytics/kpis` | ❌ Error |
| `GET /api/v1/analytics/trends` | ❌ Error |
| `GET /api/v1/analytics/metrics` | ❌ Error |

**Important Notes:**
- All reports are **read-only** (GET only)
- Reports return **simple JSON** (not JSON:API format)
- All reports are **generated on-demand** (no caching)
- Use date filters: `asOfDate`, `startDate`, `endDate` (or `filter[...]` format)
- Default currency is **MXN**

**Related Modules:**
- [Accounting Module](ACCOUNTING_FRONTEND_GUIDE.md) - Source data for financial statements
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - Source data for aging reports
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Source data for sales reports
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Source data for purchase reports
