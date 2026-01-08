# Reports Module

## Overview

Read-only reporting endpoints. All reports require authentication and appropriate permissions.

## Financial Reports

### Balance Sheet

```typescript
GET /api/v1/reports/balance-sheets?date=2026-01-08

// Response
{
  "data": {
    "type": "balance-sheets",
    "attributes": {
      "asOf": "2026-01-08",
      "assets": {
        "current": {
          "cash": 150000.00,
          "accountsReceivable": 250000.00,
          "inventory": 300000.00,
          "total": 700000.00
        },
        "fixed": {
          "property": 500000.00,
          "equipment": 150000.00,
          "lessDepreciation": -50000.00,
          "total": 600000.00
        },
        "total": 1300000.00
      },
      "liabilities": {
        "current": {
          "accountsPayable": 180000.00,
          "taxesPayable": 45000.00,
          "total": 225000.00
        },
        "longTerm": {
          "bankLoans": 300000.00,
          "total": 300000.00
        },
        "total": 525000.00
      },
      "equity": {
        "capital": 500000.00,
        "retainedEarnings": 275000.00,
        "total": 775000.00
      }
    }
  }
}
```

### Income Statement

```typescript
GET /api/v1/reports/income-statements?start_date=2026-01-01&end_date=2026-01-31

// Response
{
  "data": {
    "type": "income-statements",
    "attributes": {
      "period": { "start": "2026-01-01", "end": "2026-01-31" },
      "revenue": {
        "sales": 500000.00,
        "otherIncome": 10000.00,
        "total": 510000.00
      },
      "costOfSales": {
        "materials": 200000.00,
        "labor": 50000.00,
        "overhead": 30000.00,
        "total": 280000.00
      },
      "grossProfit": 230000.00,
      "operatingExpenses": {
        "salaries": 80000.00,
        "rent": 15000.00,
        "utilities": 5000.00,
        "depreciation": 10000.00,
        "other": 20000.00,
        "total": 130000.00
      },
      "operatingIncome": 100000.00,
      "otherExpenses": {
        "interest": 5000.00,
        "total": 5000.00
      },
      "incomeBeforeTax": 95000.00,
      "incomeTax": 28500.00,
      "netIncome": 66500.00
    }
  }
}
```

### Cash Flow Statement

```typescript
GET /api/v1/reports/cash-flow-statements?start_date=2026-01-01&end_date=2026-01-31

// Response
{
  "data": {
    "type": "cash-flow-statements",
    "attributes": {
      "period": { "start": "2026-01-01", "end": "2026-01-31" },
      "operating": {
        "netIncome": 66500.00,
        "adjustments": {
          "depreciation": 10000.00,
          "accountsReceivable": -25000.00,
          "inventory": -15000.00,
          "accountsPayable": 20000.00
        },
        "total": 56500.00
      },
      "investing": {
        "equipmentPurchase": -50000.00,
        "total": -50000.00
      },
      "financing": {
        "loanPayments": -10000.00,
        "total": -10000.00
      },
      "netChange": -3500.00,
      "beginningCash": 153500.00,
      "endingCash": 150000.00
    }
  }
}
```

### Trial Balance

```typescript
GET /api/v1/reports/trial-balances?fiscal_period_id=12

// Response
{
  "data": {
    "type": "trial-balances",
    "attributes": {
      "fiscalPeriod": { "id": 12, "name": "January 2026" },
      "accounts": [
        { "code": "1101", "name": "Caja", "debit": 50000.00, "credit": 0 },
        { "code": "1102", "name": "Bancos", "debit": 100000.00, "credit": 0 },
        { "code": "1104", "name": "Clientes", "debit": 250000.00, "credit": 0 },
        { "code": "2101", "name": "Proveedores", "debit": 0, "credit": 180000.00 },
        { "code": "4100", "name": "Ventas", "debit": 0, "credit": 500000.00 }
      ],
      "totals": { "debit": 1300000.00, "credit": 1300000.00 }
    }
  }
}
```

## Accounts Receivable Reports

### AR Aging

```typescript
GET /api/v1/reports/ar-aging-reports?as_of=2026-01-08

// Response
{
  "data": {
    "type": "ar-aging-reports",
    "attributes": {
      "asOf": "2026-01-08",
      "summary": {
        "current": 50000.00,
        "days1to30": 40000.00,
        "days31to60": 30000.00,
        "days61to90": 20000.00,
        "over90": 10000.00,
        "total": 150000.00
      },
      "byCustomer": [
        {
          "contactId": 10,
          "name": "Acme Corp",
          "current": 10000.00,
          "days1to30": 15000.00,
          "days31to60": 0,
          "days61to90": 0,
          "over90": 0,
          "total": 25000.00
        }
      ]
    }
  }
}
```

### AR Aging (Alternative Endpoint)

```typescript
GET /api/v1/ar-invoices/aging-report

// Same response as above
```

## Accounts Payable Reports

### AP Aging

```typescript
GET /api/v1/reports/ap-aging-reports?as_of=2026-01-08

// Response (similar structure to AR Aging)
{
  "data": {
    "type": "ap-aging-reports",
    "attributes": {
      "asOf": "2026-01-08",
      "summary": {
        "current": 80000.00,
        "days1to30": 50000.00,
        "days31to60": 30000.00,
        "days61to90": 15000.00,
        "over90": 5000.00,
        "total": 180000.00
      },
      "bySupplier": [...]
    }
  }
}
```

## Inventory Reports

### Inventory Valuation

```typescript
GET /api/v1/reports/inventory-valuations?as_of=2026-01-08

// Response
{
  "data": {
    "type": "inventory-valuations",
    "attributes": {
      "asOf": "2026-01-08",
      "totalValue": 300000.00,
      "byWarehouse": [
        { "warehouseId": 1, "name": "Main Warehouse", "value": 200000.00 },
        { "warehouseId": 2, "name": "Secondary", "value": 100000.00 }
      ],
      "byCategory": [
        { "categoryId": 1, "name": "Electronics", "value": 150000.00 },
        { "categoryId": 2, "name": "Accessories", "value": 150000.00 }
      ],
      "topItems": [
        { "productId": 1, "name": "iPhone 15 Pro", "quantity": 50, "value": 65000.00 }
      ]
    }
  }
}
```

### Stock Levels

```typescript
GET /api/v1/reports/stock-levels

// Response
{
  "data": {
    "type": "stock-levels",
    "attributes": {
      "summary": {
        "totalSKUs": 150,
        "inStock": 120,
        "lowStock": 20,
        "outOfStock": 10
      },
      "lowStockItems": [
        { "productId": 5, "name": "USB Cable", "current": 5, "reorderPoint": 20 }
      ],
      "outOfStockItems": [
        { "productId": 10, "name": "Screen Protector", "lastInStock": "2026-01-05" }
      ]
    }
  }
}
```

## Sales Reports

### Sales Summary

```typescript
GET /api/v1/reports/sales-summaries?start_date=2026-01-01&end_date=2026-01-31

// Response
{
  "data": {
    "type": "sales-summaries",
    "attributes": {
      "period": { "start": "2026-01-01", "end": "2026-01-31" },
      "totals": {
        "orders": 150,
        "revenue": 500000.00,
        "avgOrderValue": 3333.33,
        "itemsSold": 450
      },
      "byProduct": [
        { "productId": 1, "name": "iPhone 15 Pro", "quantity": 20, "revenue": 26000.00 }
      ],
      "byCustomer": [
        { "contactId": 10, "name": "Acme Corp", "orders": 5, "revenue": 75000.00 }
      ],
      "bySalesperson": [
        { "userId": 5, "name": "Juan Perez", "orders": 30, "revenue": 100000.00 }
      ]
    }
  }
}
```

## Analytics Endpoints

```typescript
// Dashboard summary
GET /api/v1/analytics/dashboard

// Response
{
  "revenue": { "today": 15000, "mtd": 350000, "ytd": 350000 },
  "orders": { "today": 5, "pending": 15, "shipped": 120 },
  "inventory": { "value": 300000, "lowStock": 20, "outOfStock": 10 },
  "receivables": { "total": 150000, "overdue": 25000, "overduePercent": 16.7 },
  "payables": { "total": 180000, "dueThisWeek": 40000 }
}

// KPIs
GET /api/v1/analytics/kpis

// Response
{
  "grossMargin": 46,
  "netMargin": 13.3,
  "inventoryTurnover": 6.5,
  "dso": 35,  // Days Sales Outstanding
  "dpo": 42,  // Days Payable Outstanding
  "currentRatio": 3.1,
  "quickRatio": 1.8
}

// Trends
GET /api/v1/analytics/trends?metric=revenue&period=monthly&months=12

// Response
{
  "metric": "revenue",
  "data": [
    { "period": "2025-02", "value": 450000 },
    { "period": "2025-03", "value": 480000 },
    // ... 12 months
  ]
}

// Custom metrics
GET /api/v1/analytics/metrics?metrics=revenue,orders,margin&groupBy=day&start=2026-01-01&end=2026-01-31

// Response
{
  "data": [
    { "date": "2026-01-01", "revenue": 15000, "orders": 5, "margin": 45 },
    { "date": "2026-01-02", "revenue": 18000, "orders": 6, "margin": 47 }
  ]
}
```

## Report Filters

Most reports support these common filters:

| Filter | Example |
|--------|---------|
| `date` | `?date=2026-01-08` |
| `start_date` | `?start_date=2026-01-01` |
| `end_date` | `?end_date=2026-01-31` |
| `fiscal_period_id` | `?fiscal_period_id=12` |
| `warehouse_id` | `?warehouse_id=1` |
| `category_id` | `?category_id=5` |
| `contact_id` | `?contact_id=10` |

## Export Formats

```typescript
// Export to Excel
GET /api/v1/reports/sales-summaries?start_date=2026-01-01&end_date=2026-01-31&format=xlsx

// Export to CSV
GET /api/v1/reports/ar-aging-reports?as_of=2026-01-08&format=csv

// Export to PDF
GET /api/v1/reports/income-statements?start_date=2026-01-01&end_date=2026-01-31&format=pdf
```

## Permissions

| Report Type | Required Permission |
|-------------|---------------------|
| Financial Statements | `reports.financial.view` |
| AR/AP Reports | `reports.receivables.view` |
| Inventory Reports | `reports.inventory.view` |
| Sales Reports | `reports.sales.view` |
| Analytics | `reports.analytics.view` |
