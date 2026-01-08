# Business Rules - Frontend Impact

This document lists ALL business rules that require frontend implementation or awareness.

---

## Critical Rules (Block Operations)

### CR-001: Credit Validation
**Module:** Sales, Finance
**Rule:** Sales orders validate customer credit before confirmation
**Frontend Must:**
- Show credit status on customer selection
- Display available credit amount
- Show error message when credit exceeded
- Offer "Request Approval" option for over-limit orders

```typescript
// Check before confirm
GET /api/v1/contacts/{id}/credit-status
{
  "creditLimit": 50000.00,
  "currentBalance": 35000.00,
  "availableCredit": 15000.00,
  "overdueAmount": 5000.00,
  "paymentScore": 85,
  "isOnCreditHold": false
}
```

### CR-002: Stock Availability
**Module:** Sales, Inventory
**Rule:** Cannot confirm order if insufficient stock
**Frontend Must:**
- Check stock before adding to order
- Show available quantity (not just total)
- Warn when quantity exceeds available
- Offer backorder option

```typescript
// available = quantity - reserved
GET /api/v1/stocks?filter[product_id]=1
{
  "quantity": 100,
  "reservedQuantity": 30,
  "availableQuantity": 70  // Use this
}
```

### CR-003: Balanced Journal Entries
**Module:** Accounting
**Rule:** Debits must equal credits
**Frontend Must:**
- Calculate running balance as user adds lines
- Disable save until balanced
- Show debit/credit totals in real-time

### CR-004: Period Control
**Module:** Accounting, Finance
**Rule:** Cannot post to closed/locked fiscal periods
**Frontend Must:**
- Disable closed periods in date pickers
- Show period status on forms
- Warn when approaching period close

```typescript
GET /api/v1/fiscal-periods?filter[status]=open
```

### CR-005: Status Transitions
**Module:** Sales, Purchase, Finance
**Rule:** Only valid status transitions allowed
**Frontend Must:**
- Only show valid next-status buttons
- Disable invalid transitions
- Show transition errors clearly

**Valid Sales Order Transitions:**
| From | To |
|------|----|
| draft | confirmed, cancelled |
| confirmed | processing, cancelled |
| processing | shipped, cancelled |
| shipped | delivered, returned |
| delivered | returned, completed |

---

## High Priority Rules (User Experience)

### HP-001: Purchase Approval Workflow
**Module:** Purchase
**Rule:** POs require approval based on amount
**Frontend Must:**
- Show approval status badge
- Show approval buttons for approvers
- Disable edit after approval submission
- Show approval thresholds

| Amount | Required Approval |
|--------|-------------------|
| < $5,000 | Auto-approved |
| $5,000 - $25,000 | Manager |
| $25,000 - $100,000 | Director |
| > $100,000 | Executive |

### HP-002: Budget Control
**Module:** Purchase
**Rule:** POs validate against department budgets
**Frontend Must:**
- Show budget remaining before PO creation
- Warn when PO exceeds budget
- Offer budget reallocation option

```typescript
GET /api/v1/budgets/summary
GET /api/v1/budgets/needs-attention
POST /api/v1/purchase-orders/check-budget
```

### HP-003: Three-Way Match
**Module:** Purchase, Finance
**Rule:** PO vs Receipt vs Invoice validation
**Frontend Must:**
- Show match status on AP invoices
- Highlight variances
- Show approval requirement for variances

### HP-004: Early Payment Discount
**Module:** Finance
**Rule:** 2% discount if paid within 10 days
**Frontend Must:**
- Show discount deadline prominently
- Show discount amount
- Apply discount on early payment
- Show savings after applying

```typescript
// Invoice response includes:
{
  "discountPercent": 2,
  "discountDays": 10,
  "discountDate": "2026-01-18",
  "discountAmount": 232.00,
  "isDiscountAvailable": true
}
```

### HP-005: Late Payment Penalties
**Module:** Finance
**Rule:** 1.5% monthly penalty on overdue
**Frontend Must:**
- Show penalty amount on overdue invoices
- Show days overdue
- Include penalty in payment amount

```typescript
GET /api/v1/ar-invoices/{id}/late-penalty
{
  "daysOverdue": 45,
  "penaltyRate": 1.5,
  "penaltyAmount": 261.00,
  "totalWithPenalty": 11861.00
}
```

### HP-006: Credit Hold
**Module:** Finance, Sales
**Rule:** Customers with overdue >60 days blocked
**Frontend Must:**
- Show credit hold status prominently
- Block order creation for held customers
- Show path to release hold

### HP-007: FEFO Batch Selection
**Module:** Inventory
**Rule:** First Expired First Out
**Frontend Must:**
- Show expiration dates on batch selection
- Highlight near-expiry batches
- Show batch selection order

### HP-008: Reservation System
**Module:** Inventory, Sales
**Rule:** Confirming order reserves stock
**Frontend Must:**
- Show reserved vs available
- Update stock display after confirm
- Show reservation expiration if applicable

---

## Medium Priority Rules (Convenience)

### MP-001: Duplicate Contact Detection
**Module:** Contacts
**Rule:** Check for duplicates on create
**Frontend Must:**
- Call check-duplicates before save
- Show potential duplicates dialog
- Allow merge or proceed

```typescript
GET /api/v1/contacts/check-duplicates?name=Acme&email=contact@acme.com

{
  "hasDuplicates": true,
  "duplicates": [
    { "id": 5, "name": "Acme Corp", "matchScore": 0.85 }
  ]
}
```

### MP-002: Automatic Discounts
**Module:** Sales
**Rule:** Discount rules apply automatically
**Frontend Must:**
- Show applied discounts breakdown
- Show discount eligibility hints
- Recalculate on item changes

### MP-003: Backorder Creation
**Module:** Sales, Inventory
**Rule:** Insufficient stock creates backorder
**Frontend Must:**
- Notify when backorder created
- Show backorder status
- Link to backorder management

### MP-004: Leave Balance
**Module:** HR
**Rule:** Cannot request more than available leave
**Frontend Must:**
- Show leave balance before request
- Validate days requested
- Show by leave type

### MP-005: Payment Application FIFO
**Module:** Finance
**Rule:** Payments apply to oldest invoices first
**Frontend Must:**
- Show payment allocation
- Display which invoices paid
- Allow manual override if permitted

### MP-006: Cart Expiration
**Module:** Ecommerce
**Rule:** Carts expire after 24 hours
**Frontend Must:**
- Show expiration timer
- Warn before expiration
- Handle expired cart gracefully

### MP-007: Document Expiration
**Module:** Contacts
**Rule:** Track document expiration dates
**Frontend Must:**
- Show expiring documents warnings
- Highlight expired documents
- Send reminder notifications

---

## Auto-Generated Data (Backend Creates)

| Action | Auto Creates | Frontend Action |
|--------|--------------|-----------------|
| Complete Sales Order | AR Invoice | Navigate to invoice |
| Receive Purchase Order | AP Invoice + Inventory Entry | Navigate to invoice |
| AR Invoice Posted | Journal Entry | Show GL link |
| AP Invoice Posted | Journal Entry | Show GL link |
| Inventory Movement | Journal Entry (COGS) | Transparent |
| Payment Applied | Invoice Status Update | Refresh invoice |
| Lead Converted | Contact + Opportunity | Navigate to opportunity |
| Cart Checkout + Payment | Sales Order + Invoice | Navigate to order confirmation |

---

## Validation Rules Summary

### Required Fields by Entity

**SalesOrder:**
- contactId (must be is_customer=true)
- orderDate
- totalAmount

**PurchaseOrder:**
- contactId (must be is_supplier=true)
- orderDate
- totalAmount

**ARInvoice:**
- contactId
- invoiceDate
- dueDate
- fiscalPeriodId (must be open)

**JournalEntry:**
- date
- fiscalPeriodId (must be open)
- minimum 2 lines
- total_debit = total_credit

**Employee:**
- employeeCode (unique)
- firstName, lastName
- departmentId
- positionId
- hireDate
- status

**Lead:**
- title
- source
- companyName
- contactPerson
- email

---

## Event Flows for Frontend

### Order-to-Cash Flow
```
1. Customer selects products
2. Frontend checks stock availability
3. Frontend checks customer credit
4. Create SalesOrder (draft)
5. Add SalesOrderItems
6. Apply discount rules
7. Confirm order (reserves stock)
8. Process order (pick/pack)
9. Ship order (update tracking)
10. Complete order -> AR Invoice created automatically
11. Customer pays -> Payment applied
12. Invoice marked paid
```

### Procure-to-Pay Flow
```
1. Create PurchaseOrder (draft)
2. Add PurchaseOrderItems
3. Check budget
4. Submit for approval
5. Approval process
6. Order approved -> Send to supplier
7. Receive goods -> Inventory updated
8. Receive invoice -> AP Invoice created
9. Three-way match validation
10. Schedule payment
11. Process payment
12. Invoice marked paid
```

### Ecommerce Checkout Flow
```
1. Add items to cart
2. Apply coupon (optional)
3. Start checkout session
4. Enter shipping address
5. Select shipping method
6. Create payment intent (Stripe)
7. Confirm payment (Stripe.js)
8. Backend confirms payment
9. SalesOrder created automatically
10. ARInvoice created automatically
11. Email confirmation sent
12. Redirect to order confirmation
```

---

## Error Messages to Handle

| Error Code | Message | Frontend Action |
|------------|---------|-----------------|
| `CREDIT_EXCEEDED` | Credit limit exceeded | Show available credit, offer approval |
| `INSUFFICIENT_STOCK` | Insufficient stock | Show available, offer backorder |
| `PERIOD_CLOSED` | Fiscal period is closed | Select different period |
| `ENTRY_NOT_BALANCED` | Debits do not equal credits | Show difference, highlight |
| `DUPLICATE_DETECTED` | Potential duplicate found | Show duplicates, confirm |
| `APPROVAL_REQUIRED` | Requires approval | Show submission form |
| `BUDGET_EXCEEDED` | Budget exceeded | Show remaining, request override |
| `INVALID_TRANSITION` | Invalid status transition | Disable button, show valid options |
| `PAYMENT_FAILED` | Payment processing failed | Show Stripe error, retry |
