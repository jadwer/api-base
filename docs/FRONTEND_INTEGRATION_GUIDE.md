# Frontend Integration Guide

**Last Updated:** 2026-01-06
**API Version:** v1.1
**JSON:API Specification:** 1.1
**Base URL:** `/api/v1`

## Overview

This guide provides frontend developers with comprehensive documentation for integrating with the API. Each module has its own detailed guide with specific field mappings, examples, and TypeScript interfaces.

## Table of Contents

- [Authentication](#authentication)
- [Common JSON:API Patterns](#common-jsonapi-patterns)
- [Module Guides](#module-guides)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)
- [Quick Reference](#quick-reference)

---

## Authentication

### Login

```javascript
const response = await fetch('/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'admin@example.com',
    password: 'password'
  })
});

const data = await response.json();
const token = data.token; // Store this token for subsequent requests
```

### Using the Token

All subsequent requests must include the authentication token:

```javascript
const headers = {
  'Content-Type': 'application/vnd.api+json',
  'Accept': 'application/vnd.api+json',
  'Authorization': `Bearer ${token}`
};
```

---

## Common JSON:API Patterns

### Fetching a List of Resources

```javascript
const response = await fetch('/api/v1/{resource}', { headers });
const data = await response.json();

console.log(data);
// {
//   data: [...],
//   links: { first, last, prev, next },
//   meta: { page: { currentPage, from, to, lastPage, total } }
// }
```

### Fetching a Single Resource

```javascript
const response = await fetch('/api/v1/{resource}/123', { headers });
const data = await response.json();

console.log(data);
// {
//   data: { id, type, attributes, relationships }
// }
```

### Creating a Resource

```javascript
const payload = {
  data: {
    type: "resources",
    attributes: {
      fieldName: "value",
      anotherField: "value"
    }
  }
};

const response = await fetch('/api/v1/{resource}', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});

const data = await response.json();
```

### Updating a Resource (PATCH)

```javascript
const payload = {
  data: {
    type: "resources",
    id: "123",
    attributes: {
      fieldName: "new value"
    }
  }
};

const response = await fetch('/api/v1/{resource}/123', {
  method: 'PATCH',
  headers,
  body: JSON.stringify(payload)
});

const data = await response.json();
```

### Deleting a Resource

```javascript
const response = await fetch('/api/v1/{resource}/123', {
  method: 'DELETE',
  headers
});

// 204 No Content on success
```

### Including Relationships

Use the `include` query parameter:

```javascript
const response = await fetch(
  '/api/v1/{resource}/123?include=relation1,relation2',
  { headers }
);
```

### Filtering

```javascript
const response = await fetch(
  '/api/v1/{resource}?filter[fieldName]=value&filter[anotherField]=value',
  { headers }
);
```

### Sorting

```javascript
// Ascending
const response = await fetch('/api/v1/{resource}?sort=fieldName', { headers });

// Descending
const response = await fetch('/api/v1/{resource}?sort=-fieldName', { headers });

// Multiple fields
const response = await fetch('/api/v1/{resource}?sort=-field1,field2', { headers });
```

### Pagination

```javascript
const response = await fetch(
  '/api/v1/{resource}?page[number]=2&page[size]=20',
  { headers }
);
```

### Sparse Fieldsets

Request only specific fields to optimize performance:

```javascript
const response = await fetch(
  '/api/v1/{resource}?fields[resources]=field1,field2,field3',
  { headers }
);
```

---

## v1.1 Updates (2026-01-06)

This section documents ALL new features, entities, and endpoints added in v1.1. Frontend must implement these to be fully compatible.

### E2E Online Sales Flow

**Full E2E Documentation:** See [E2E_TESTING_GUIDE.md](E2E_TESTING_GUIDE.md) for complete frontend implementation guide.

The complete online sales flow is now implemented and tested:
```
Cart -> Checkout -> SalesOrder -> ARInvoice -> GL Posting
```

Key behaviors:
- Contact is automatically created from user email during checkout (or reused if exists)
- ARInvoice is automatically created when SalesOrder status changes to 'confirmed'
- GL Journal Entry is automatically posted for revenue/receivables
- Checkout sessions expire after 30 minutes

---

### Stripe Payment Integration

New payment gateway integration for e-commerce checkout:

**Endpoints:**
- `POST /api/v1/checkout-sessions/{id}/payment-intent` - Create Stripe PaymentIntent
- `PATCH /api/v1/checkout-sessions/{id}` - Confirm payment with paymentIntentId

**Frontend Implementation:**
```javascript
// 1. Create payment intent
const intentResponse = await api.post(`/checkout-sessions/${sessionId}/payment-intent`);
const { clientSecret } = intentResponse.data.attributes;

// 2. Use Stripe.js to confirm payment
const { paymentIntent, error } = await stripe.confirmCardPayment(clientSecret, {
  payment_method: paymentMethodId
});

// 3. Update checkout session
await api.patch(`/checkout-sessions/${sessionId}`, {
  data: {
    type: 'checkout-sessions',
    id: sessionId,
    attributes: {
      status: 'payment_confirmed',
      paymentIntentId: paymentIntent.id
    }
  }
});
```

---

### Cycle Count Scheduling (IV-M001)

New inventory cycle counting system:

**Endpoints:**
- `GET /api/v1/cycle-counts` - List cycle counts
- `GET /api/v1/cycle-counts/{id}` - Get single cycle count
- `POST /api/v1/cycle-counts` - Create/schedule cycle count
- `PATCH /api/v1/cycle-counts/{id}` - Update cycle count (record results)
- `DELETE /api/v1/cycle-counts/{id}` - Delete cycle count

**TypeScript Interface:**
```typescript
interface CycleCountAttributes {
  countNumber: string;
  scheduledDate: string;
  completedDate?: string;
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
  systemQuantity: number;
  countedQuantity?: number;
  varianceQuantity?: number;  // Computed: counted - system
  varianceValue?: number;     // Computed: variance * unit cost
  abcClass?: 'A' | 'B' | 'C';
  notes?: string;
  metadata?: Record<string, any>;
  // Computed fields
  hasVariance: boolean;
  variancePercentage?: number;
  createdAt: string;
  updatedAt: string;
}

interface CycleCountRelationships {
  warehouse: { data: { type: 'warehouses'; id: string } };
  warehouseLocation?: { data: { type: 'warehouse-locations'; id: string } };
  product: { data: { type: 'products'; id: string } };
  assignedTo?: { data: { type: 'users'; id: string } };
  countedBy?: { data: { type: 'users'; id: string } };
}
```

**Filters:**
- `filter[status]=scheduled`
- `filter[abcClass]=A`
- `filter[warehouse]=1`
- `filter[product]=123`
- `filter[assignedTo]=5`

---

### Duplicate Contact Detection (CO-M001)

New service for detecting duplicate contacts:

**Usage in ContactRequest:**
The backend now validates tax_id uniqueness automatically. When creating/updating contacts, if a duplicate tax_id is found, a 422 error is returned.

**Frontend can proactively check:**
```javascript
// Check for duplicates before creating
GET /api/v1/contacts?filter[taxId]=ABC123456789
GET /api/v1/contacts?filter[email]=user@example.com
```

**Duplicate Detection Levels:**
- **Definite:** Same tax_id (RFC) - blocked automatically
- **Probable:** Same email - warning shown
- **Possible:** Similar name (>80% similarity) - suggestion shown

---

### Automatic Discount Rules (SA-M003)

New automatic discount system for sales orders:

**Endpoints:**
- `GET /api/v1/discount-rules` - List discount rules
- `GET /api/v1/discount-rules/{id}` - Get single rule
- `POST /api/v1/discount-rules` - Create rule
- `PATCH /api/v1/discount-rules/{id}` - Update rule
- `DELETE /api/v1/discount-rules/{id}` - Delete rule

**TypeScript Interface:**
```typescript
interface DiscountRuleAttributes {
  name: string;
  code: string;
  description?: string;
  discountType: 'percentage' | 'fixed' | 'buy_x_get_y';
  discountValue: number;
  buyQuantity?: number;     // For buy_x_get_y
  getQuantity?: number;     // For buy_x_get_y
  appliesTo: 'order' | 'product' | 'category';
  minOrderAmount?: number;
  minQuantity?: number;
  maxDiscountAmount?: number;
  productIds?: number[];
  categoryIds?: number[];
  customerIds?: number[];
  customerClassifications?: string[];
  startDate?: string;
  endDate?: string;
  usageLimit?: number;
  usagePerCustomer?: number;
  currentUsage: number;     // Read-only
  priority: number;
  isCombinable: boolean;
  isActive: boolean;
  // Computed fields
  isValid: boolean;
  isExpired: boolean;
  usageRemaining?: number;
  createdAt: string;
  updatedAt: string;
}
```

**Discount Types:**
- `percentage`: X% off (e.g., 10% off)
- `fixed`: Fixed amount off (e.g., $50 off)
- `buy_x_get_y`: Buy X get Y free (e.g., Buy 2 Get 1)

**Filters:**
- `filter[discountType]=percentage`
- `filter[appliesTo]=order`
- `filter[isActive]=true`
- `filter[code]=SUMMER2026`

**Validate Discount Code:**
```javascript
// Check if code is valid for order
GET /api/v1/discount-rules?filter[code]=SUMMER2026&filter[isActive]=true

// The response includes isValid computed field
```

---

### Early Payment Discount (FI-M002)

New early payment discount fields on AR Invoices:

**New Fields in ar-invoices:**
```typescript
interface ARInvoiceEarlyPaymentFields {
  discountPercent?: number;      // e.g., 2.0 for 2%
  discountDays?: number;         // e.g., 10 for "2/10 Net 30"
  discountDate?: string;         // Deadline for discount
  discountAmount?: number;       // Calculated: total * percent
  discountApplied: boolean;      // Was discount taken?
  discountAppliedAmount?: number;// Actual discount given
  discountAppliedDate?: string;  // When discount was applied
}
```

**Common Payment Terms:**
- `2/10 Net 30`: 2% discount if paid within 10 days
- `1/15 Net 45`: 1% discount if paid within 15 days
- `3/5 Net 30`: 3% discount if paid within 5 days

**New Filter:**
```javascript
// Get invoices with available early payment discount
GET /api/v1/ar-invoices?filter[withAvailableDiscount]=true
```

**Frontend Display:**
```javascript
// Check if discount is available
if (invoice.discountDate && new Date(invoice.discountDate) > new Date()) {
  const savings = invoice.discountAmount;
  const deadline = invoice.discountDate;
  showBanner(`Pay by ${deadline} to save $${savings}!`);
}
```

---

### Budget Control (PU-M003)

New Budget Control system for Purchase Orders:

**Endpoints:**
- `GET /api/v1/budgets` - List all budgets
- `GET /api/v1/budgets/{id}` - Get single budget
- `POST /api/v1/budgets` - Create budget
- `PATCH /api/v1/budgets/{id}` - Update budget
- `DELETE /api/v1/budgets/{id}` - Delete budget
- `GET /api/v1/budgets/summary` - Budget summary dashboard
- `GET /api/v1/budgets/needs-attention` - Budgets requiring attention

**TypeScript Interface:**
```typescript
interface BudgetAttributes {
  name: string;
  code: string;
  description?: string;
  budgetType: 'department' | 'category' | 'project' | 'supplier' | 'general';
  departmentCode?: string;
  categoryId?: number;
  projectCode?: string;
  contactId?: number;
  periodType: 'monthly' | 'quarterly' | 'annual' | 'custom';
  startDate: string;
  endDate: string;
  fiscalYear?: number;
  budgetedAmount: number;
  committedAmount: number;  // Amount allocated to POs
  spentAmount: number;      // Amount actually spent
  availableAmount: number;  // Computed: budgeted - committed - spent
  warningThreshold?: number;  // Default 80%
  criticalThreshold?: number; // Default 95%
  hardLimit: boolean;
  allowOvercommit: boolean;
  isActive: boolean;
  // Computed fields
  utilizationPercent: number; // (committed + spent) / budgeted * 100
  statusLevel: 'normal' | 'warning' | 'critical' | 'exceeded';
}
```

**Filters:**
- `filter[budgetType]=department`
- `filter[periodType]=monthly`
- `filter[isActive]=true`
- `filter[fiscalYear]=2026`
- `filter[current]` - Active budgets for current date
- `filter[overWarning]` - Budgets over warning threshold
- `filter[overCritical]` - Budgets over critical threshold

**Relationships:**
- `category` - Product category (if budget type is category)
- `contact` - Supplier contact (if budget type is supplier)
- `allocations` - Budget allocations to purchase orders

---

## Previous Updates (2026-01-02)

### Sales Order Amount Fields

The `sales-orders` resource now includes additional amount fields for clearer financial breakdown:

```typescript
interface SalesOrderAttributes {
  // ... existing fields ...
  discountTotal: number;   // Total discounts applied
  subtotal: number;        // Sum of line items before discounts (NEW)
  taxAmount: number;       // Total tax amount (NEW)
  totalAmount: number;     // Final amount: subtotal - discountTotal + taxAmount
}
```

**Formula:** `totalAmount = subtotal - discountTotal + taxAmount`

### CRM Activities

The Activities entity now includes:
- 5 activity types: `call`, `email`, `meeting`, `note`, `task`
- 4 statuses: `scheduled`, `completed`, `cancelled`, `pending`
- Duration tracking and scheduling fields
- Relationships to Leads, Campaigns, Opportunities

### SystemHealth Module

New system monitoring endpoints available for admin/god roles:
- `GET /api/v1/system-health` - Full system health status
- `GET /api/v1/system-health/ping` - Public uptime check (no auth required)
- `GET /api/v1/system-health/database` - Database health
- `GET /api/v1/system-health/storage` - Storage health
- `GET /api/v1/system-health/queue` - Queue health
- `GET /api/v1/system-health/errors` - Recent error summary
- `GET /api/v1/system-health/metrics` - Performance metrics

---

## Module Guides

Each module has a dedicated frontend integration guide with detailed information:

### Core Business Modules

- **[Product Module](modules/PRODUCT_FRONTEND_GUIDE.md)** - Products, Categories, Brands, Units
- **[Inventory Module](modules/INVENTORY_FRONTEND_GUIDE.md)** - Warehouses, Stock, Batches, Movements
- **[Purchase Module](modules/PURCHASE_FRONTEND_GUIDE.md)** - Purchase Orders, Suppliers
- **[Sales Module](modules/SALES_FRONTEND_GUIDE.md)** - Sales Orders, Customers, Order Tracking

### E-commerce & Customer Engagement

- **[Ecommerce Module](modules/ECOMMERCE_FRONTEND_GUIDE.md)** - Cart, Checkout, Payments, Wishlists, Reviews, Recommendations
- **[CRM Module](modules/CRM_FRONTEND_GUIDE.md)** - Leads, Campaigns, Pipeline Stages (900+ lines)

### Financial & Accounting

- **[Finance Module](modules/FINANCE_FRONTEND_GUIDE.md)** - AR/AP Invoices, Payments, Bank Accounts
- **[Accounting Module](modules/ACCOUNTING_FRONTEND_GUIDE.md)** - Chart of Accounts, Journal Entries, Fiscal Periods
- **[Billing/CFDI Module](modules/BILLING_FRONTEND_GUIDE.md)** - Mexican Electronic Invoicing (CFDI 4.0), PAC Integration

### Human Resources & Reporting

- **[HR Module](modules/HR_FRONTEND_GUIDE.md)** - Employees, Payroll, Attendance, Leave Management
- **[Reports Module](modules/REPORTS_FRONTEND_GUIDE.md)** - Financial Statements, Analytics, KPIs

### Supporting Modules

- **[Contacts Module](modules/CONTACTS_FRONTEND_GUIDE.md)** - Contact Management, Addresses, Documents
- **[Audit Module](modules/AUDIT_FRONTEND_GUIDE.md)** - Activity Logs (37 models, 50% coverage), Login History

---

## Error Handling

### Standard Error Response Format

```javascript
{
  errors: [
    {
      status: "422",
      title: "Validation Error",
      detail: "The name field is required.",
      source: { pointer: "/data/attributes/name" }
    }
  ]
}
```

### Common HTTP Status Codes

- **200 OK** - Successful GET or PATCH request
- **201 Created** - Successful POST request
- **204 No Content** - Successful DELETE request
- **400 Bad Request** - Malformed request
- **401 Unauthorized** - Missing or invalid authentication token
- **403 Forbidden** - Insufficient permissions
- **404 Not Found** - Resource not found
- **422 Unprocessable Entity** - Validation errors
- **500 Internal Server Error** - Server-side error

### Error Handling Example

```javascript
async function handleApiRequest(url, options) {
  try {
    const response = await fetch(url, options);

    if (!response.ok) {
      const errorData = await response.json();

      if (response.status === 422) {
        // Validation errors
        const validationErrors = errorData.errors.map(err => ({
          field: err.source.pointer.split('/').pop(),
          message: err.detail
        }));
        console.error('Validation errors:', validationErrors);
      } else if (response.status === 401) {
        // Redirect to login
        window.location.href = '/login';
      } else if (response.status === 403) {
        // Show permission error
        console.error('You do not have permission to perform this action');
      }

      throw new Error(errorData.errors[0].detail);
    }

    return await response.json();
  } catch (error) {
    console.error('API request failed:', error);
    throw error;
  }
}
```

---

## Best Practices

### 1. API Client Pattern

Create a reusable API client:

```javascript
class ApiClient {
  constructor(baseURL, token) {
    this.baseURL = baseURL;
    this.token = token;
  }

  get headers() {
    return {
      'Content-Type': 'application/vnd.api+json',
      'Accept': 'application/vnd.api+json',
      'Authorization': `Bearer ${this.token}`
    };
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    const config = {
      ...options,
      headers: {
        ...this.headers,
        ...options.headers
      }
    };

    const response = await fetch(url, config);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.errors[0].detail);
    }

    if (response.status === 204) return null;
    return await response.json();
  }

  get(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;
    return this.request(url, { method: 'GET' });
  }

  post(endpoint, data) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  patch(endpoint, data) {
    return this.request(endpoint, {
      method: 'PATCH',
      body: JSON.stringify(data)
    });
  }

  delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }
}

// Usage
const api = new ApiClient('/api/v1', token);
const products = await api.get('/products', { 'filter[isActive]': 'true' });
```

### 2. Response Caching

Implement caching for frequently accessed data:

```javascript
class CachedApiClient extends ApiClient {
  constructor(baseURL, token, cacheDuration = 300000) { // 5 minutes default
    super(baseURL, token);
    this.cache = new Map();
    this.cacheDuration = cacheDuration;
  }

  async get(endpoint, params = {}) {
    const cacheKey = `${endpoint}?${JSON.stringify(params)}`;
    const cached = this.cache.get(cacheKey);

    if (cached && Date.now() - cached.timestamp < this.cacheDuration) {
      return cached.data;
    }

    const data = await super.get(endpoint, params);
    this.cache.set(cacheKey, { data, timestamp: Date.now() });
    return data;
  }

  invalidateCache(pattern) {
    for (const key of this.cache.keys()) {
      if (key.includes(pattern)) {
        this.cache.delete(key);
      }
    }
  }
}
```

### 3. Retry Logic for Failed Requests

```javascript
async function fetchWithRetry(url, options, retries = 3, delay = 1000) {
  for (let i = 0; i < retries; i++) {
    try {
      return await fetch(url, options);
    } catch (error) {
      if (i === retries - 1) throw error;
      await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
    }
  }
}
```

### 4. TypeScript Integration

Use TypeScript interfaces for type safety:

```typescript
interface JsonApiResource<T> {
  data: {
    id: string;
    type: string;
    attributes: T;
    relationships?: Record<string, any>;
  };
}

interface JsonApiCollection<T> {
  data: Array<{
    id: string;
    type: string;
    attributes: T;
  }>;
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    page: {
      currentPage: number;
      from: number;
      to: number;
      lastPage: number;
      total: number;
    };
  };
}
```

### 5. Field Name Conventions

**IMPORTANT**: The API uses camelCase in JSON:API responses but snake_case in the database.

- **JSON:API (Frontend):** `createdAt`, `updatedAt`, `isActive`, `unitId`
- **Database (Backend):** `created_at`, `updated_at`, `is_active`, `unit_id`

The conversion is automatic - always use camelCase when sending/receiving data from the API.

---

## Quick Reference

### All Available Endpoints (736+ Total)

**Product Module** (20 endpoints):
- `/products`, `/categories`, `/brands`, `/units`

**Inventory Module** (30 endpoints):
- `/warehouses`, `/warehouse-locations`, `/stocks`, `/product-batches`, `/inventory-movements`
- `/cycle-counts` (NEW v1.1: IV-M001)

**Purchase Module** (38 endpoints):
- `/purchase-orders`, `/purchase-order-items`, `/suppliers`
- `/budgets`, `/budgets/summary`, `/budgets/needs-attention` (NEW v1.1: PU-M003)

**Sales Module** (29 endpoints):
- `/sales-orders`, `/sales-order-items`, `/customers`, `/order-tracking`
- `/discount-rules` (NEW v1.1: SA-M003)

**Ecommerce Module** (72 endpoints):
- `/shopping-carts`, `/cart-items`, `/checkout-sessions`, `/payment-transactions`
- `/wishlists`, `/wishlist-items`, `/product-reviews`, `/coupons`
- `/shipping-methods`, `/currencies`, `/product-recommendations`
- `/checkout-sessions/{id}/payment-intent` (NEW v1.1: Stripe)

**Finance Module** (40 endpoints):
- `/ar-invoices`, `/ap-invoices`, `/payments`, `/payment-applications`
- `/bank-accounts`, `/payment-methods`
- NEW v1.1: Early payment discount fields on ar-invoices (FI-M002)

**Accounting Module** (30 endpoints):
- `/accounts`, `/journal-entries`, `/journal-lines`, `/journals`
- `/fiscal-periods`, `/exchange-rates`, `/account-balances`

**HR Module** (49 endpoints):
- `/employees`, `/departments`, `/positions`, `/attendances`
- `/leave-types`, `/leaves`, `/payroll-periods`, `/payroll-items`, `/performance-reviews`

**Reports Module** (30 endpoints):
- `/balance-sheets`, `/income-statements`, `/cash-flows`, `/trial-balances`
- `/ar-aging-reports`, `/ap-aging-reports`, `/sales-reports`, `/inventory-valuation-reports`

**Billing/CFDI Module** (30 endpoints):
- `/cfdi-invoices`, `/cfdi-concepts`, `/company-settings`
- `/cfdi-invoices/{id}/stamp`, `/cfdi-invoices/{id}/cancel`, `/cfdi-invoices/{id}/pdf`

**CRM Module** (25 endpoints):
- `/pipeline-stages`, `/leads`, `/campaigns`, `/activities`, `/opportunities`

**Contacts Module** (20 endpoints):
- `/contacts`, `/contact-addresses`, `/contact-people`, `/contact-documents`
- NEW v1.1: Duplicate detection validation on tax_id (CO-M001)

---

## Support

For module-specific integration details, examples, and TypeScript interfaces, refer to the individual module guides listed above.

For API issues or questions:
- Review the specific module guide
- Check error responses for validation details
- Ensure proper JSON:API format compliance
- Verify authentication token is valid
