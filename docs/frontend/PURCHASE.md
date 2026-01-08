# Purchase Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| PurchaseOrder | `/api/v1/purchase-orders` | Purchase orders |
| PurchaseOrderItem | `/api/v1/purchase-order-items` | Order line items |
| Budget | `/api/v1/budgets` | Department budgets |
| BudgetAllocation | `/api/v1/budget-allocations` | Budget usage |

## Purchase Order

```typescript
type POStatus = 'draft' | 'pending_approval' | 'approved' | 'ordered' | 'partial' | 'received' | 'cancelled';

interface PurchaseOrder {
  id: string;
  contactId: number;         // Supplier (is_supplier=true)
  orderNumber: string;       // Auto: PO-XXXXXXXX
  status: POStatus;
  orderDate: string;
  expectedDate: string | null;
  subtotal: number;
  taxAmount: number;
  totalAmount: number;
  notes: string | null;

  // Approval
  approvedAt: string | null;
  approvedById: number | null;

  // Finance integration
  apInvoiceId: number | null;

  createdAt: string;
}

// List orders
GET /api/v1/purchase-orders?filter[status]=approved&include=contact,items&sort=-orderDate

// Create order
POST /api/v1/purchase-orders
{
  "data": {
    "type": "purchase-orders",
    "attributes": {
      "contactId": 5,
      "orderNumber": "PO-2026-001",
      "orderDate": "2026-01-08",
      "expectedDate": "2026-01-15",
      "status": "draft",
      "subtotal": 10000.00,
      "taxAmount": 1600.00,
      "totalAmount": 11600.00,
      "notes": "Urgent order"
    }
  }
}
```

### Status Flow

```
draft → pending_approval → approved → ordered → partial → received
             ↓                  ↓                    ↓
         cancelled          cancelled            cancelled
```

### Approval Workflow

| Amount | Approval Required |
|--------|-------------------|
| < $5,000 | Auto-approved |
| $5,000 - $25,000 | Manager approval |
| $25,000 - $100,000 | Director approval |
| > $100,000 | Executive approval |

```typescript
// Submit for approval
POST /api/v1/purchase-orders/{id}/submit-for-approval

// Approve order (requires permission)
POST /api/v1/purchase-orders/{id}/approve
{
  "notes": "Approved for Q1 inventory"
}

// Reject order
POST /api/v1/purchase-orders/{id}/reject
{
  "reason": "Budget exceeded"
}

// Get approval status
GET /api/v1/purchase-orders/{id}/approval-status

// Response
{
  "status": "pending_approval",
  "requiredLevel": "manager",
  "submittedAt": "2026-01-08T10:00:00Z",
  "submittedBy": { "id": 1, "name": "John Doe" },
  "approvers": [
    { "level": "manager", "user": null, "status": "pending" }
  ]
}
```

## Purchase Order Item

```typescript
interface PurchaseOrderItem {
  id: string;
  purchaseOrderId: number;
  productId: number;
  quantity: number;
  receivedQuantity: number;
  unitPrice: number;
  taxRate: number;           // Default 16%
  total: number;

  createdAt: string;
}

// Get items for order
GET /api/v1/purchase-order-items?filter[purchase_order_id]=1&include=product

// Create item
POST /api/v1/purchase-order-items
{
  "data": {
    "type": "purchase-order-items",
    "attributes": {
      "purchaseOrderId": 1,
      "productId": 10,
      "quantity": 100,
      "unitPrice": 50.00,
      "taxRate": 16,
      "total": 5800.00
    }
  }
}
```

## Receiving

```typescript
// Receive order items
POST /api/v1/purchase-orders/{id}/receive
{
  "items": [
    { "itemId": 1, "receivedQuantity": 100, "batchNumber": "LOT-2026-001" },
    { "itemId": 2, "receivedQuantity": 50, "notes": "50 pending" }
  ],
  "receivedDate": "2026-01-10",
  "notes": "Partial delivery"
}

// Response includes inventory movements created
{
  "received": true,
  "inventoryMovements": [
    { "id": 101, "productId": 10, "quantity": 100, "movementType": "entry" },
    { "id": 102, "productId": 15, "quantity": 50, "movementType": "entry" }
  ],
  "apInvoice": { "id": 5, "invoiceNumber": "AP-2026-001" }  // If fully received
}
```

## Budget Control

```typescript
type BudgetStatus = 'active' | 'exhausted' | 'closed';

interface Budget {
  id: string;
  name: string;
  fiscalYear: number;
  categoryId: number | null;     // For specific category
  contactId: number | null;      // For specific supplier
  budgetAmount: number;
  usedAmount: number;
  remainingAmount: number;       // Calculated
  status: BudgetStatus;
  startDate: string;
  endDate: string;
}

// Get budgets
GET /api/v1/budgets?filter[fiscal_year]=2026&filter[status]=active

// Get budget summary
GET /api/v1/budgets/summary

// Response
{
  "totalBudget": 500000.00,
  "totalUsed": 125000.00,
  "totalRemaining": 375000.00,
  "budgets": [
    { "id": 1, "name": "IT Equipment", "remaining": 50000.00 },
    { "id": 2, "name": "Office Supplies", "remaining": 25000.00 }
  ]
}

// Get budgets needing attention
GET /api/v1/budgets/needs-attention

// Response
{
  "overBudget": [
    { "id": 3, "name": "Marketing", "overage": 5000.00 }
  ],
  "nearLimit": [
    { "id": 4, "name": "Travel", "remaining": 1000.00, "percentUsed": 95 }
  ]
}
```

### Budget Validation on PO

```typescript
// Check budget before creating PO
POST /api/v1/purchase-orders/check-budget
{
  "categoryId": 5,
  "amount": 10000.00
}

// Response
{
  "approved": true,
  "budget": {
    "id": 1,
    "name": "IT Equipment",
    "remaining": 50000.00
  }
}

// OR
{
  "approved": false,
  "error": "Budget exceeded",
  "budget": {
    "id": 1,
    "name": "IT Equipment",
    "remaining": 8000.00,
    "requested": 10000.00,
    "overage": 2000.00
  }
}
```

## Three-Way Match

```typescript
// Get match status for PO
GET /api/v1/purchase-orders/{id}/three-way-match

// Response
{
  "status": "matched",  // or "variance_detected", "pending"
  "purchaseOrder": {
    "total": 11600.00,
    "items": [{ "productId": 10, "quantity": 100, "unitPrice": 50.00 }]
  },
  "receipt": {
    "total": 11600.00,
    "items": [{ "productId": 10, "receivedQuantity": 100 }]
  },
  "invoice": {
    "total": 11600.00,
    "items": [{ "productId": 10, "quantity": 100, "unitPrice": 50.00 }]
  },
  "variances": []  // Empty if matched
}

// OR with variances
{
  "status": "variance_detected",
  "variances": [
    {
      "type": "price",
      "productId": 10,
      "poPrice": 50.00,
      "invoicePrice": 55.00,
      "variance": 5.00,
      "percentVariance": 10
    }
  ],
  "requiresApproval": true
}
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Supplier Validation | Contact must have is_supplier=true | Filter contacts |
| Approval Workflow | Amount-based approval tiers | Show approval buttons |
| Budget Control | Validates against active budget | Show budget remaining |
| Three-Way Match | PO vs Receipt vs Invoice | Show match status |
| Receiving Tolerance | Max 5% over-receive | Validate quantities |
| Auto AP Invoice | Full receipt creates AP Invoice | Navigate to invoice |

## Complete PO Workflow

```typescript
async function createPurchaseOrder(supplierId: number, items: POItem[], budgetId?: number) {
  // 1. Validate supplier
  const supplier = await fetch(`/api/v1/contacts/${supplierId}`, { headers });
  const supplierData = await supplier.json();
  if (!supplierData.data.attributes.isSupplier) {
    throw new Error('Contact is not a supplier');
  }

  // 2. Check budget (if applicable)
  if (budgetId) {
    const total = items.reduce((sum, item) => sum + item.total, 0);
    const budgetCheck = await fetch('/api/v1/purchase-orders/check-budget', {
      method: 'POST',
      headers,
      body: JSON.stringify({ budgetId, amount: total })
    });
    const budget = await budgetCheck.json();
    if (!budget.approved) {
      throw new Error(`Budget exceeded. Remaining: $${budget.budget.remaining}`);
    }
  }

  // 3. Create PO
  const orderResponse = await fetch('/api/v1/purchase-orders', {
    method: 'POST',
    headers,
    body: JSON.stringify({
      data: {
        type: 'purchase-orders',
        attributes: {
          contactId: supplierId,
          orderNumber: generatePONumber(),
          orderDate: new Date().toISOString().split('T')[0],
          status: 'draft',
          totalAmount: items.reduce((sum, item) => sum + item.total, 0)
        }
      }
    })
  });
  const order = await orderResponse.json();

  // 4. Create items
  for (const item of items) {
    await fetch('/api/v1/purchase-order-items', {
      method: 'POST',
      headers,
      body: JSON.stringify({
        data: {
          type: 'purchase-order-items',
          attributes: {
            purchaseOrderId: parseInt(order.data.id),
            productId: item.productId,
            quantity: item.quantity,
            unitPrice: item.unitPrice,
            taxRate: 16,
            total: item.total
          }
        }
      })
    });
  }

  // 5. Submit for approval (if amount requires it)
  await fetch(`/api/v1/purchase-orders/${order.data.id}/submit-for-approval`, {
    method: 'POST',
    headers
  });

  return order;
}
```
