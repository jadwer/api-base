# Module Relationships - Coherence Guide

This document maps all relationships between modules to ensure frontend coherence.

---

## Module Dependency Graph

```
                    ┌──────────────┐
                    │   Contacts   │
                    │ (Customers & │
                    │  Suppliers)  │
                    └──────┬───────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌───────────────┐  ┌───────────────┐  ┌───────────────┐
│     Sales     │  │   Purchase    │  │      CRM      │
│ (Sales Orders)│  │(Purchase Ords)│  │(Leads/Opps)   │
└───────┬───────┘  └───────┬───────┘  └───────────────┘
        │                  │
        ▼                  ▼
┌───────────────┐  ┌───────────────┐
│   Inventory   │◄─│   Inventory   │
│ (Reservations)│  │   (Receipts)  │
└───────┬───────┘  └───────┬───────┘
        │                  │
        ▼                  ▼
┌───────────────┐  ┌───────────────┐
│    Finance    │  │    Finance    │
│ (AR Invoices) │  │ (AP Invoices) │
└───────┬───────┘  └───────┬───────┘
        │                  │
        └────────┬─────────┘
                 ▼
        ┌───────────────┐
        │  Accounting   │
        │(Journal Entry)│
        └───────┬───────┘
                │
                ▼
        ┌───────────────┐
        │    Reports    │
        └───────────────┘
```

---

## Entity Relationships

### Contact (Central Entity)

**Contact is used by:**
| Module | Entity | Relationship | Filter |
|--------|--------|--------------|--------|
| Sales | SalesOrder | contact_id | is_customer=true |
| Purchase | PurchaseOrder | contact_id | is_supplier=true |
| Finance | ARInvoice | contact_id | is_customer=true |
| Finance | APInvoice | contact_id | is_supplier=true |
| Finance | ARPayment | contact_id | is_customer=true |
| Finance | APPayment | contact_id | is_supplier=true |
| CRM | Lead | contact_id | (after conversion) |
| CRM | Opportunity | contact_id | is_customer=true |
| Billing | CfdiInvoice | contact_id | is_customer=true |
| HR | Employee | contact_id | is_employee=true |

**Frontend Rule:** When selecting contacts, ALWAYS filter by appropriate role flag.

```typescript
// Customer selector
GET /api/v1/contacts?filter[is_customer]=true&filter[is_active]=true

// Supplier selector
GET /api/v1/contacts?filter[is_supplier]=true&filter[is_active]=true
```

### Product (Catalog Entity)

**Product is used by:**
| Module | Entity | Field |
|--------|--------|-------|
| Sales | SalesOrderItem | product_id |
| Purchase | PurchaseOrderItem | product_id |
| Inventory | Stock | product_id |
| Inventory | InventoryMovement | product_id |
| Inventory | ProductBatch | product_id |
| Ecommerce | CartItem | product_id |
| Ecommerce | WishlistItem | product_id |
| Ecommerce | ProductReview | product_id |
| Billing | CfdiItem | product_id |

### User (Actor Entity)

**User is referenced by:**
| Module | Entity | Field | Purpose |
|--------|--------|-------|---------|
| Sales | SalesOrder | created_by | Order creator |
| Purchase | PurchaseOrder | approved_by | Approver |
| Finance | ARPayment | created_by | Payment recorder |
| Accounting | JournalEntry | posted_by | GL poster |
| HR | Employee | user_id | Linked account |
| CRM | Lead | user_id | Assigned rep |
| CRM | Activity | user_id | Task owner |
| Audit | Audit | causer_id | Action performer |

---

## Cross-Module Flows

### 1. Sales Order -> AR Invoice -> Journal Entry

```typescript
// Step 1: Create Sales Order
POST /api/v1/sales-orders
Response: { id: 1, status: 'draft' }

// Step 2: Add Items
POST /api/v1/sales-order-items
{ salesOrderId: 1, productId: 5, quantity: 10 }

// Step 3: Confirm Order (reserves inventory)
POST /api/v1/orders/1/status
{ status: 'confirmed' }

// Step 4: Complete Order (backend auto-creates AR Invoice)
POST /api/v1/orders/1/status
{ status: 'delivered' }

// AR Invoice is now linked:
GET /api/v1/sales-orders/1
Response: { arInvoiceId: 10 }

// Journal Entry was created for AR Invoice:
GET /api/v1/ar-invoices/10
Response: { journalEntryId: 50 }
```

### 2. Purchase Order -> Inventory -> AP Invoice

```typescript
// Step 1: Create PO
POST /api/v1/purchase-orders
Response: { id: 1, status: 'draft' }

// Step 2: Submit for Approval
POST /api/v1/purchase-orders/1/submit-for-approval

// Step 3: Approve
POST /api/v1/purchase-orders/1/approve

// Step 4: Receive Goods (backend creates inventory movement + AP invoice)
POST /api/v1/purchase-orders/1/receive
{
  items: [{ itemId: 1, receivedQuantity: 100 }]
}
Response: {
  inventoryMovements: [{ id: 101 }],
  apInvoice: { id: 5 }
}
```

### 3. Lead -> Contact + Opportunity

```typescript
// Step 1: Create Lead
POST /api/v1/leads
Response: { id: 1, status: 'new' }

// Step 2: Qualify Lead
PATCH /api/v1/leads/1
{ status: 'qualified' }

// Step 3: Convert (backend creates Contact + Opportunity)
POST /api/v1/leads/1/convert
{
  create_contact: true,
  create_opportunity: true
}
Response: {
  contact: { id: 15 },
  opportunity: { id: 8 }
}
```

### 4. Cart -> Checkout -> Payment -> Order -> Invoice

```typescript
// Step 1: Get/Create Cart
POST /api/v1/shopping-carts/get-or-create
Response: { id: 1 }

// Step 2: Add Items
POST /api/v1/cart-items
{ shoppingCartId: 1, productId: 5, quantity: 2 }

// Step 3: Create Checkout Session
POST /api/v1/checkout-sessions
{ shoppingCartId: 1, shippingAddressId: 5 }
Response: { id: 1, total: 11600.00 }

// Step 4: Create Payment Intent (Stripe)
POST /api/v1/online-payments/create-intent
{ checkout_session_id: 1, amount: 11600 }
Response: { client_secret: 'pi_xxx_secret_xxx' }

// Step 5: Confirm with Stripe.js
// (frontend)

// Step 6: Confirm Payment (backend creates order + invoice)
POST /api/v1/online-payments/confirm
{ payment_intent_id: 'pi_xxx' }
Response: {
  sales_order_id: 15,
  ar_invoice_id: 20
}
```

---

## Shared Data Patterns

### 1. Fiscal Period (Required for All Financial Operations)

```typescript
// Always include open fiscal periods in date-related forms
GET /api/v1/fiscal-periods?filter[status]=open

// Before posting any financial transaction:
// 1. Check period is open
// 2. Transaction date falls within period
// 3. Validate user has posting permission
```

### 2. Currency Handling

```typescript
// Default currency: MXN
// Multi-currency supported

// Get exchange rates
GET /api/v1/exchange-rates?filter[from_currency]=USD&filter[to_currency]=MXN&sort=-effectiveDate

// Apply rate on foreign currency transactions
amount_mxn = amount_usd * exchange_rate
```

### 3. Sequence Numbers

| Entity | Format | Example |
|--------|--------|---------|
| SalesOrder | SO-XXXXXXXX | SO-ABC12345 |
| PurchaseOrder | PO-XXXXXXXX | PO-DEF67890 |
| ARInvoice | AR-XXXXX | AR-12345 |
| APInvoice | AP-XXXXX | AP-67890 |
| JournalEntry | JE-YYYY-NNNN | JE-2026-0001 |
| Payment | PAY-XXXXX | PAY-54321 |
| Employee | EMP-NNN | EMP-001 |

**Frontend:** Never generate these - always use backend-generated values.

---

## Include Paths Reference

### Sales Order with Full Context
```typescript
GET /api/v1/sales-orders/1?include=contact,items.product,arInvoice
```

### Purchase Order with Full Context
```typescript
GET /api/v1/purchase-orders/1?include=contact,items.product,apInvoice,approvedBy
```

### AR Invoice with Full Context
```typescript
GET /api/v1/ar-invoices/1?include=contact,salesOrder,journalEntry,cfdiInvoice
```

### Journal Entry with Full Context
```typescript
GET /api/v1/journal-entries/1?include=lines.account,fiscalPeriod,approvedBy,postedBy
```

---

## Navigation Paths

### From Sales Order
- View Customer: `/contacts/{contactId}`
- View Invoice: `/ar-invoices/{arInvoiceId}`
- View Products: `/products/{productId}` (for each item)

### From AR Invoice
- View Customer: `/contacts/{contactId}`
- View Order: `/sales-orders/{salesOrderId}`
- View Journal Entry: `/journal-entries/{journalEntryId}`
- View CFDI: `/cfdi-invoices/{cfdiInvoiceId}`

### From Journal Entry
- View Source: `/{sourceType}/{sourceId}` (dynamic based on source)
- View Period: `/fiscal-periods/{fiscalPeriodId}`

### From Lead
- View Assigned User: `/users/{userId}`
- After Conversion:
  - View Contact: `/contacts/{contactId}`
  - View Opportunity: `/opportunities/{opportunityId}`

---

## Data Consistency Rules

### 1. Contact Updates
When contact changes (name, email, RFC):
- All linked invoices display updated info
- All linked orders show updated customer name
- CFDI may need re-generation if RFC changes

### 2. Product Updates
When product changes (price):
- Existing order items keep original price
- New orders use new price
- Inventory value may need recalculation

### 3. Fiscal Period Close
When period closes:
- No more postings allowed
- Reports become final
- Balances are locked

### 4. Order Completion
When sales order completes:
- Stock reservations become actual exits
- AR Invoice is created
- GL entries are posted
- Cannot revert to draft

---

## Frontend State Management

### Recommended Store Structure
```typescript
interface AppState {
  auth: {
    user: User;
    token: string;
  };

  // Shared entities (cached)
  contacts: Record<number, Contact>;
  products: Record<number, Product>;
  warehouses: Record<number, Warehouse>;
  accounts: Record<number, Account>;
  fiscalPeriods: FiscalPeriod[];

  // Module-specific state
  sales: {
    currentOrder: SalesOrder | null;
    orders: SalesOrder[];
  };
  finance: {
    arInvoices: ARInvoice[];
    apInvoices: APInvoice[];
  };
  // ... etc
}
```

### Cache Invalidation
| Action | Invalidate |
|--------|------------|
| Create/Update Contact | contacts cache |
| Create/Update Product | products cache |
| Complete Order | orders list, invoices list, stock cache |
| Post Payment | invoices list, payments list |
| Close Period | fiscalPeriods list |
