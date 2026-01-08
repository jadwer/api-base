# Frontend Integration - Master Guide

**Version:** 1.0 | **Updated:** 2026-01-08 | **API:** JSON:API 5.x

---

## Quick Start

### Authentication
```typescript
// Login
const response = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'admin@example.com', password: 'secureadmin' })
});
const { token } = await response.json();

// Use token in all requests
const headers = {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/vnd.api+json',
  'Accept': 'application/vnd.api+json'
};
```

### Test Credentials
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | secureadmin |
| Tech | tech@example.com | securetech |
| Customer | customer@example.com | customer |

---

## API Conventions

### JSON:API Format
```typescript
// CREATE/UPDATE Request
{
  "data": {
    "type": "resource-type",  // kebab-case plural
    "attributes": {
      "fieldName": "value"    // camelCase
    },
    "relationships": {
      "relatedResource": {
        "data": { "type": "type", "id": "1" }
      }
    }
  }
}

// Response
{
  "data": {
    "type": "resource-type",
    "id": "1",
    "attributes": { ... },
    "relationships": { ... }
  },
  "included": [ ... ]  // When using ?include=
}
```

### Common Query Parameters
| Parameter | Example | Description |
|-----------|---------|-------------|
| `include` | `?include=contact,items` | Include relationships |
| `filter[field]` | `?filter[status]=active` | Filter by field (snake_case) |
| `sort` | `?sort=-createdAt,name` | Sort (- for descending) |
| `page[number]` | `?page[number]=2` | Pagination |
| `page[size]` | `?page[size]=25` | Items per page |

### Filter Convention: Use snake_case
```typescript
// CORRECT
?filter[order_date]=2026-01-08
?filter[payment_status]=paid
?filter[is_active]=true

// WRONG (will fail)
?filter[orderDate]=2026-01-08
?filter[paymentStatus]=paid
```

---

## Module Index

| Module | Entities | Key Endpoints | Doc |
|--------|----------|---------------|-----|
| **Auth** | Login, Logout | `/api/auth/*` | [AUTH.md](AUTH.md) |
| **User** | Users | `/api/v1/users` | [USER.md](USER.md) |
| **PermissionManager** | Roles, Permissions | `/api/v1/roles` | [PERMISSION_MANAGER.md](PERMISSION_MANAGER.md) |
| **Product** | Products, Categories, Brands, Units | `/api/v1/products` | [PRODUCT.md](PRODUCT.md) |
| **Inventory** | Warehouses, Stock, Movements, Batches | `/api/v1/warehouses` | [INVENTORY.md](INVENTORY.md) |
| **Contacts** | Contacts, Addresses, Documents | `/api/v1/contacts` | [CONTACTS.md](CONTACTS.md) |
| **Sales** | SalesOrders, Items, Shipments, Backorders | `/api/v1/sales-orders` | [SALES.md](SALES.md) |
| **Purchase** | PurchaseOrders, Items, Budgets | `/api/v1/purchase-orders` | [PURCHASE.md](PURCHASE.md) |
| **Finance** | AR/AP Invoices, Payments, BankAccounts | `/api/v1/ar-invoices` | [FINANCE.md](FINANCE.md) |
| **Accounting** | Accounts, JournalEntries, FiscalPeriods | `/api/v1/accounts` | [ACCOUNTING.md](ACCOUNTING.md) |
| **Ecommerce** | Carts, Checkout, Wishlists, Reviews | `/api/v1/shopping-carts` | [ECOMMERCE.md](ECOMMERCE.md) |
| **HR** | Employees, Attendance, Payroll, Leave | `/api/v1/employees` | [HR.md](HR.md) |
| **CRM** | Leads, Campaigns, Activities, Opportunities | `/api/v1/leads` | [CRM.md](CRM.md) |
| **Billing** | CFDI, Stamping, PAC Integration | `/api/v1/cfdi-invoices` | [BILLING.md](BILLING.md) |
| **Reports** | Financial, Management, KPIs | `/api/v1/reports/*` | [REPORTS.md](REPORTS.md) |
| **Audit** | Activity Logs | `/api/v1/audits` | [AUDIT.md](AUDIT.md) |
| **PageBuilder** | CMS Pages | `/api/v1/pages` | [PAGEBUILDER.md](PAGEBUILDER.md) |
| **SystemHealth** | Health Checks | `/api/v1/system-health` | [SYSTEMHEALTH.md](SYSTEMHEALTH.md) |

---

## Critical Business Rules

These rules are enforced by the backend and affect frontend behavior.

### 1. Sales Orders
| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Credit Check | Orders validate customer credit limit | Show error if credit exceeded |
| Status Flow | draft -> confirmed -> processing -> shipped -> delivered | Disable invalid transitions |
| Auto Invoice | Completing order creates AR Invoice | Navigate to invoice after completion |
| Reservations | Confirming reserves inventory | Show available stock before confirm |

### 2. Purchase Orders
| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Approval Workflow | Orders >$10K need approval | Show approval status/buttons |
| Budget Control | Validates against department budget | Show budget remaining |
| Auto Invoice | Receiving creates AP Invoice | Navigate to invoice after receive |
| Three-Way Match | PO vs Receipt vs Invoice validation | Show match status |

### 3. Finance
| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Credit Hold | Overdue customers are blocked | Show warning, prevent orders |
| Early Payment Discount | 2% discount if paid in 10 days | Show discount amount/deadline |
| Late Penalties | 1.5%/month after due date | Show penalty amount |
| Payment Application | Payments applied to oldest invoices first | Show application breakdown |

### 4. Inventory
| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| FEFO | First Expired First Out for batches | Show expiration dates |
| Negative Stock | Cannot go negative (except adjustments) | Validate before movement |
| Reservations | Stock can be reserved for orders | Show available vs reserved |
| Cycle Counts | Scheduled inventory audits | Show pending counts |

### 5. Accounting
| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Balanced Entries | Debits must equal credits | Validate before save |
| Period Control | Cannot post to closed periods | Disable closed periods |
| Immutable Posted | Posted entries cannot be edited | Hide edit button for posted |

---

## Common Patterns

### 1. Create Entity with Items
```typescript
// Step 1: Create parent
const order = await createOrder(orderData);

// Step 2: Create items
for (const item of items) {
  await createOrderItem({ ...item, salesOrderId: order.id });
}

// Step 3: Recalculate totals (if needed)
await updateOrder(order.id, { totalAmount: calculateTotal(items) });
```

### 2. Status Transitions
```typescript
// Use custom endpoint for status changes
await fetch(`/api/v1/sales-orders/${id}/confirm`, {
  method: 'POST',
  headers
});

// Or update status field directly (if allowed)
await fetch(`/api/v1/sales-orders/${id}`, {
  method: 'PATCH',
  headers,
  body: JSON.stringify({
    data: {
      type: 'sales-orders',
      id: id,
      attributes: { status: 'confirmed' }
    }
  })
});
```

### 3. Relationship Include
```typescript
// Get order with customer and items
const response = await fetch(
  '/api/v1/sales-orders/1?include=contact,items.product',
  { headers }
);
const { data, included } = await response.json();

// Parse included resources
const contact = included.find(r => r.type === 'contacts');
const items = included.filter(r => r.type === 'sales-order-items');
```

---

## Error Handling

### Standard Error Response
```typescript
{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "The order number has already been taken.",
      "source": { "pointer": "/data/attributes/orderNumber" }
    }
  ]
}
```

### Business Rule Errors
```typescript
{
  "message": "Credit validation failed",
  "errors": {
    "credit_validation": ["Credit limit exceeded. Available: $10,000.00"]
  },
  "credit_analysis": {
    "current_balance": 40000.00,
    "requested_amount": 50000.00,
    "credit_limit": 50000.00,
    "available_credit": 10000.00
  }
}
```

---

## Health Check

```typescript
// System health endpoint (no auth required)
GET /api/v1/system-health

// Response
{
  "status": "healthy",
  "services": {
    "database": { "status": "up", "responseTime": 5 },
    "cache": { "status": "up", "responseTime": 2 },
    "queue": { "status": "up", "pending": 0 }
  },
  "version": "1.0.0",
  "timestamp": "2026-01-08T10:00:00Z"
}
```

---

## File Structure

```
docs/frontend/
├── README.md               # This file - Master reference
├── AUTH.md                 # Authentication (login/logout)
├── USER.md                 # User management
├── PERMISSION_MANAGER.md   # Roles & permissions
├── PRODUCT.md              # Product catalog
├── INVENTORY.md            # Stock management
├── CONTACTS.md             # Customer/Supplier management
├── SALES.md                # Sales orders
├── PURCHASE.md             # Purchase orders
├── FINANCE.md              # AR/AP, Payments
├── ACCOUNTING.md           # GL, Journal entries
├── ECOMMERCE.md            # Shopping cart, checkout
├── HR.md                   # Human resources
├── CRM.md                  # Customer relationship
├── BILLING.md              # CFDI, invoicing
├── REPORTS.md              # Financial reports
├── AUDIT.md                # Activity logs
├── PAGEBUILDER.md          # CMS pages
├── SYSTEMHEALTH.md         # Health checks
├── BUSINESS_RULES.md       # Complete rules reference
├── MODULE_RELATIONSHIPS.md # Module dependencies
└── FRONTEND_PROMPT.md      # Prompt for Claude frontend
```

---

**Next:** Read the specific module documentation for detailed endpoints and examples.
