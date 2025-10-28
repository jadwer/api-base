# Entity Lifecycle Documentation

**Date:** 2025-10-28
**Version:** 1.0
**Status:** Production-Ready Phase 3 Complete

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Lifecycle Diagram File](#lifecycle-diagram-file)
3. [1. AR Invoice Lifecycle](#1-ar-invoice-lifecycle)
4. [2. AP Invoice Lifecycle](#2-ap-invoice-lifecycle)
5. [3. Journal Entry Lifecycle](#3-journal-entry-lifecycle)
6. [4. Fiscal Period Lifecycle](#4-fiscal-period-lifecycle)
5. [5. Sales Order Lifecycle](#5-sales-order-lifecycle)
6. [6. Purchase Order Lifecycle](#6-purchase-order-lifecycle)
7. [7. Shopping Cart Lifecycle](#7-shopping-cart-lifecycle)
8. [Automatic State Transitions](#automatic-state-transitions)
9. [Validation Rules](#validation-rules)
10. [Permission Requirements](#permission-requirements)

---

## Overview

This document provides comprehensive documentation for the lifecycle and state transitions of all major entities in the Laravel Modular ERP system. Each entity follows a defined state machine with validation rules, permission checks, and automatic transitions.

### Key Principles

- **Immutability**: Posted financial records cannot be edited (only reversed)
- **Audit Trail**: All state changes logged with before/after data
- **Validation**: Transitions validated at service layer
- **Permissions**: Role-based access control per transition
- **Automation**: System-triggered transitions where appropriate

---

## Lifecycle Diagram File

The complete state machine diagram is available in DrawIO format:
- **File**: `LIFECYCLE-state-machines.drawio`
- **Contents**: 7 entity state machines with complete transitions
- **Format**: DrawIO XML (editable at [diagrams.net](https://app.diagrams.net))

---

## 1. AR Invoice Lifecycle

### States Overview

| State | Description | Editable | Deletable | Final |
|-------|-------------|----------|-----------|-------|
| **draft** | Initial creation | ✅ Yes | ✅ Yes | ❌ No |
| **pending_approval** | Awaiting manager approval | ❌ No | ❌ No | ❌ No |
| **approved** | Approved, ready to post | ❌ No | ❌ No | ❌ No |
| **posted** | Posted to GL, awaiting payment | ❌ No | ❌ No | ❌ No |
| **partially_paid** | Partial payment received | ❌ No | ❌ No | ❌ No |
| **paid** | Fully paid | ❌ No | ❌ No | ✅ Yes |
| **overdue** | Past due date, unpaid | ❌ No | ❌ No | ❌ No |
| **cancelled** | Cancelled before posting | ❌ No | ✅ Yes | ✅ Yes |

### State Transition Matrix

| From State | To State | Trigger | Actor | Conditions |
|------------|----------|---------|-------|------------|
| **draft** | pending_approval | Submit for approval | Creator | amount > threshold OR first-time customer |
| **draft** | cancelled | Cancel invoice | Finance Manager | Before posting only |
| **pending_approval** | approved | Approve invoice | Sales Manager / Finance Manager / CFO | Based on approval tier |
| **pending_approval** | draft | Reject (back to draft) | Manager | Requires corrections |
| **approved** | posted | Post to GL | System (automatic) | Invoice linked to GL journal entry |
| **posted** | partially_paid | Apply partial payment | Finance | 0 < paid_amount < total_amount |
| **posted** | paid | Apply full payment | Finance | paid_amount = total_amount |
| **posted** | overdue | System check | System (scheduled job) | due_date < today AND unpaid |
| **partially_paid** | paid | Apply remaining payment | Finance | remaining_balance = 0 |
| **partially_paid** | overdue | System check | System (scheduled job) | due_date < today |
| **overdue** | paid | Late payment | Finance | Full payment received |

### Business Rules

#### 1. Approval Requirements

**Tier 1 (CFO Approval Required)**:
- Amount > $100,000
- Always requires CFO sign-off

**Tier 2 (Finance Manager Approval)**:
- Amount > $50,000
- Requires Finance Manager approval

**Tier 3 (Sales Manager Approval)**:
- Amount > $10,000 OR first-time customer
- Requires Sales Manager approval

**Auto-Approve**:
- Amount ≤ $10,000 AND established customer (prior orders exist)
- Skips approval, goes directly to approved

#### 2. Credit Validation

Before approval, system validates:
```php
CreditManagementService::validateCustomerCredit($contactId, $invoiceAmount);

// Checks:
1. Credit Limit: currentARBalance + invoiceAmount <= contact.credit_limit
2. Overdue Amount: No overdue invoices exist
3. Payment Score: (on_time_payments / total_paid_invoices) × 100 >= 60%
```

**If Credit Check Fails**:
- Invoice remains in `pending_approval`
- Manager notified for manual review
- Can override with reason

#### 3. GL Posting Rules

When `approved` → `posted`:
```
Automatic Journal Entry Created:
DR  Accounts Receivable (1210)    $total_amount
    CR  Sales Revenue (4010)           $subtotal
    CR  VAT Payable (2120)             $tax_amount
```

**Fields Updated**:
- `invoice.journal_entry_id` = created journal entry ID
- `invoice.gl_posting_status` = 'posted'
- `invoice.gl_posted_at` = now()

#### 4. Payment Application Rules

**Partial Payment**:
- Updates `invoice.paid_amount` += payment_amount
- Updates `invoice.remaining_balance` (generated column = total - paid)
- Status changes to `partially_paid` if 0 < paid < total

**Full Payment**:
- `invoice.remaining_balance` = 0
- Status changes to `paid`
- `invoice.paid_date` = payment_date
- No further payments can be applied

**Payment GL Entry** (Automatic):
```
DR  Bank Account (1110)               $payment_amount
    CR  Accounts Receivable (1210)        $payment_amount
```

#### 5. Overdue Detection

**Scheduled Job** (runs daily at 00:00):
```php
ARInvoice::where('status', 'posted')
    ->where('due_date', '<', now()->toDateString())
    ->update(['status' => 'overdue']);

ARInvoice::where('status', 'partially_paid')
    ->where('due_date', '<', now()->toDateString())
    ->update(['status' => 'overdue']);
```

**Actions Triggered**:
- Email notification to customer
- Email notification to Finance Manager
- Aged AR report updated
- Collection workflow initiated (if > 30 days overdue)

### API Endpoints

**State Transitions**:
```http
# Submit for approval
POST /api/v1/ar-invoices/{id}/submit-for-approval

# Approve
POST /api/v1/ar-invoices/{id}/approve

# Post to GL (automatic, but can be triggered manually)
POST /api/v1/ar-invoices/{id}/post

# Apply payment
POST /api/v1/ar-invoices/{id}/apply-payment
{
  "payment_id": 123,
  "amount": 11600.00
}

# Cancel
POST /api/v1/ar-invoices/{id}/cancel
{
  "reason": "Customer request - order cancelled"
}
```

### Database Schema

**Status Column**:
```sql
status ENUM(
    'draft',
    'pending_approval',
    'approved',
    'posted',
    'partially_paid',
    'paid',
    'overdue',
    'cancelled'
) DEFAULT 'draft'
```

**Audit Fields**:
```sql
created_by BIGINT UNSIGNED NOT NULL,
approved_by BIGINT UNSIGNED NULL,
approved_at TIMESTAMP NULL,
gl_posted_at TIMESTAMP NULL,
paid_date DATE NULL
```

---

## 2. AP Invoice Lifecycle

### Overview

AP Invoice (Accounts Payable) follows the **same status flow as AR Invoice** with these key differences:

### Differences from AR Invoice

| Aspect | AR Invoice | AP Invoice |
|--------|------------|------------|
| **Direction** | Money IN (receivable) | Money OUT (payable) |
| **Contact Type** | Customer (is_customer=true) | Supplier (is_supplier=true) |
| **Payment Type** | 'received' | 'sent' |
| **GL Entry** | DR AR, CR Revenue | DR Expense, CR AP |
| **Approval Tiers** | $10k, $50k, $100k | $5k, $50k, $100k |
| **Validation** | Credit limit check | Budget check (optional) |

### GL Posting Rules

When `approved` → `posted`:
```
DR  Cost of Goods Sold / Expense (5010)   $subtotal
DR  VAT Recoverable (1310)                $tax_amount
    CR  Accounts Payable (2110)               $total_amount
```

When payment made:
```
DR  Accounts Payable (2110)        $payment_amount
    CR  Bank Account (1110)            $payment_amount
```

### Three-Way Match (Optional)

**Enhanced Control for High-Value Suppliers**:

Validates consistency between:
1. **Purchase Order**: Ordered quantity, unit price, total
2. **Receipt Document**: Received quantity, quality inspection
3. **Supplier Invoice**: Invoiced quantity, amount

**Validation Rules**:
- Quantities must match (tolerance: ±1%)
- Prices within tolerance (±5%)
- Totals match within rounding ($0.50)

**If Mismatch Detected**:
- Invoice remains in `pending_approval`
- Discrepancy report generated
- Purchasing Manager notified
- Requires approval to proceed

---

## 3. Journal Entry Lifecycle

### States Overview

| State | Description | Editable | Reversible | Final |
|-------|-------------|----------|------------|-------|
| **draft** | Creating entry | ✅ Yes | ❌ N/A | ❌ No |
| **pending_approval** | Awaiting accountant review | ❌ No | ❌ N/A | ❌ No |
| **approved** | Approved, ready to post | ❌ No | ❌ N/A | ❌ No |
| **posted** | Posted to GL, **IMMUTABLE** | ❌ No | ✅ Yes | ❌ No |
| **reversed** | Reversal entry created | ❌ No | ❌ No | ✅ Yes |
| **cancelled** | Cancelled before posting | ❌ No | ❌ No | ✅ Yes |

### State Transition Matrix

| From State | To State | Trigger | Actor | Conditions |
|------------|----------|---------|-------|------------|
| **draft** | pending_approval | Submit for review | Accountant | Lines added, entry balanced |
| **draft** | cancelled | Cancel entry | Accountant | Before submission |
| **pending_approval** | approved | Approve entry | Senior Accountant | Entry balanced, period open |
| **pending_approval** | draft | Reject (corrections needed) | Senior Accountant | Requires changes |
| **approved** | posted | Post to GL | Accountant | Validation passed |
| **posted** | reversed | Reverse entry | Senior Accountant | Creates reversal entry |

### Business Rules

#### 1. Balance Validation

**Required Before Posting**:
```php
if ($entry->total_debit != $entry->total_credit) {
    throw new ValidationException('Journal entry not balanced');
}

// total_debit and total_credit calculated by MySQL triggers:
CREATE TRIGGER journal_lines_after_insert
AFTER INSERT ON journal_lines
FOR EACH ROW
UPDATE journal_entries
SET total_debit = (SELECT SUM(debit_amount) FROM journal_lines WHERE journal_entry_id = NEW.journal_entry_id),
    total_credit = (SELECT SUM(credit_amount) FROM journal_lines WHERE journal_entry_id = NEW.journal_entry_id)
WHERE id = NEW.journal_entry_id;
```

**Minimum Requirements**:
- At least 2 journal lines
- Each line: debit_amount > 0 XOR credit_amount > 0 (never both, never neither)
- total_debit = total_credit (balanced)

#### 2. Period Validation

**Posting Rules**:
```php
$period = FiscalPeriod::findOrFail($entry->fiscal_period_id);

if ($period->status === 'closed') {
    throw new ValidationException('Cannot post to closed period');
}

if ($period->status === 'locked') {
    if (!auth()->user()->can('override-period-lock')) {
        throw new UnauthorizedException('Period locked - override permission required');
    }
}

// 'open' period: anyone can post
```

**Date Validation**:
```php
if ($entry->entry_date < $period->start_date || $entry->entry_date > $period->end_date) {
    throw new ValidationException('Entry date must be within fiscal period');
}
```

#### 3. Immutability Rule

**After Posting**:
- **Cannot** edit entry
- **Cannot** edit lines
- **Cannot** delete entry
- **Cannot** change amounts
- **Can** reverse (creates new entry)

**Why Immutable?**
- Financial integrity
- Audit compliance
- SAT Mexico requirements (7-15 year retention)

#### 4. Reversal Process

**Steps**:
1. Senior Accountant initiates reversal
2. System validates:
   - Original entry exists
   - Original entry status = 'posted'
   - Reason provided
3. System creates NEW journal entry:
   - Same accounts
   - **Opposite debit/credit amounts**
   - Description: "Reversal of JE-2025-0123: [reason]"
   - Links via `reversal_of_entry_id`
4. Original entry status → 'reversed'
5. New entry automatically posted

**Example**:
```
Original Entry (JE-2025-0123):
DR  Cash (1110)              $1,000.00
    CR  Revenue (4010)           $1,000.00

Reversal Entry (JE-2025-0124):
DR  Revenue (4010)           $1,000.00
    CR  Cash (1110)              $1,000.00
Description: "Reversal of JE-2025-0123: Invoice cancelled due to customer request"
```

### Database Schema

**Key Fields**:
```sql
status ENUM('draft', 'pending_approval', 'approved', 'posted', 'reversed', 'cancelled') DEFAULT 'draft',
total_debit DECIMAL(15,2) DEFAULT 0.00,  -- Calculated by trigger
total_credit DECIMAL(15,2) DEFAULT 0.00, -- Calculated by trigger
posted_by BIGINT UNSIGNED NULL,
posted_at TIMESTAMP NULL,
approved_by BIGINT UNSIGNED NULL,
approved_at TIMESTAMP NULL,
reversed_by_entry_id BIGINT UNSIGNED NULL,  -- Links to reversal entry
reversal_of_entry_id BIGINT UNSIGNED NULL   -- If this is a reversal
```

---

## 4. Fiscal Period Lifecycle

### States Overview

| State | Description | Posting Allowed | Reversible |
|-------|-------------|-----------------|------------|
| **open** | Active period | ✅ Anyone | ✅ Yes |
| **locked** | End-of-period processing | ⚠️ Override permission only | ✅ Yes |
| **closed** | Finalized | ❌ No posting | ⚠️ Emergency only (CFO) |

### State Transition Matrix

| From State | To State | Trigger | Actor | Conditions |
|------------|----------|---------|-------|------------|
| **open** | locked | Lock period | Accountant | End of accounting period |
| **locked** | open | Unlock period | Senior Accountant | Corrections needed |
| **locked** | closed | Close period | Senior Accountant | After reconciliation complete |
| **closed** | locked | Reopen (EMERGENCY) | CFO | Critical error found, requires investigation |

### Business Rules

#### 1. Posting Rules by Status

**Open Period**:
```php
// Anyone with journal entry creation permission can post
if ($period->status === 'open') {
    return true; // Allow posting
}
```

**Locked Period**:
```php
// Only users with override permission can post
if ($period->status === 'locked') {
    if (auth()->user()->can('override-period-lock')) {
        Log::warning("Override used to post to locked period {$period->code}", [
            'user_id' => auth()->id(),
            'entry_id' => $entry->id
        ]);
        return true; // Allow with warning
    }
    throw new ValidationException('Period locked - posting not allowed');
}
```

**Closed Period**:
```php
// NO posting allowed
if ($period->status === 'closed') {
    throw new ValidationException('Period closed - posting permanently disabled');
}
```

#### 2. Period Lock Workflow

**Typical Month-End Process**:

**Day 1-25**: Period = 'open'
- Normal posting allowed
- Day-to-day transactions

**Day 26-30**: Period = 'locked'
```php
// Senior Accountant locks period
$period->update([
    'status' => 'locked',
    'locked_by' => auth()->id(),
    'locked_at' => now()
]);
```

**Actions During Lock**:
- Review all entries
- Run reconciliation reports
- Correct errors (with override permission)
- Verify balances

**Day 31+**: Period = 'closed'
```php
// After verification complete
$period->update([
    'status' => 'closed',
    'closed_by' => auth()->id(),
    'closed_at' => now()
]);
```

**After Close**:
- Period is **permanent**
- Opening balances for next period calculated
- Financial statements finalized

#### 3. Emergency Reopen

**Scenario**: Critical error discovered after period closed

**Process**:
1. CFO approval required
2. Document reason in `critical_action_logs`
3. Reopen to 'locked' (not 'open'):
   ```php
   // CFO only
   if (!auth()->user()->hasRole('CFO')) {
       throw new UnauthorizedException('Only CFO can reopen closed periods');
   }

   $period->update([
       'status' => 'locked',
       'reopened_by' => auth()->id(),
       'reopened_at' => now(),
       'reopen_reason' => $request->input('reason')
   ]);

   CriticalActionLog::create([
       'log_type' => 'fiscal_period',
       'action' => 'reopen_closed_period',
       'entity_type' => 'FiscalPeriod',
       'entity_id' => $period->id,
       'user_id' => auth()->id(),
       'reason' => $request->input('reason'),
       'before_data' => ['status' => 'closed'],
       'after_data' => ['status' => 'locked']
   ]);
   ```

#### 4. Period Statistics

**Tracked Metrics**:
```php
$stats = PeriodControlService::getPeriodStatistics($periodId);

// Returns:
[
    'total_entries' => 1250,
    'total_debit' => 5_432_100.00,
    'total_credit' => 5_432_100.00,
    'balanced' => true,
    'entries_by_status' => [
        'posted' => 1200,
        'reversed' => 50
    ],
    'entries_by_journal' => [
        'GJ' => 100,  // General Journal
        'SJ' => 500,  // Sales Journal
        'PJ' => 300,  // Purchase Journal
        'CRJ' => 200, // Cash Receipts
        'CDJ' => 150  // Cash Disbursements
    ]
]
```

### Database Schema

```sql
status ENUM('open', 'locked', 'closed') DEFAULT 'open',
locked_by BIGINT UNSIGNED NULL,
locked_at TIMESTAMP NULL,
closed_by BIGINT UNSIGNED NULL,
closed_at TIMESTAMP NULL
```

---

## 5. Sales Order Lifecycle

### States Overview

| State | Description | Editable | Cancellable |
|-------|-------------|----------|-------------|
| **pending** | Created, awaiting approval | ✅ Yes | ✅ Yes |
| **approved** | Manager approved | ❌ No | ✅ Yes |
| **in_progress** | Picking/packing | ❌ No | ⚠️ Manager only |
| **completed** | Shipped, triggers invoice | ❌ No | ❌ No |
| **(invoicing_status)** | `fully_invoiced` | ❌ No | ❌ No |
| **cancelled** | Customer cancellation | ❌ No | ❌ N/A |

### State Transition Matrix

| From State | To State | Trigger | Actor | Conditions |
|------------|----------|---------|-------|------------|
| **pending** | approved | Approve order | Sales Manager | Credit check passed, approval tier met |
| **pending** | cancelled | Cancel order | Customer / Sales Rep | Before processing |
| **approved** | in_progress | Start processing | Warehouse | Inventory reserved |
| **approved** | cancelled | Cancel order | Sales Manager | Before processing |
| **in_progress** | completed | Ship order | Warehouse | All items picked and shipped |
| **in_progress** | cancelled | Cancel order | Sales Manager | Emergency only, refund |
| **completed** | (invoicing_status=fully_invoiced) | Event: SalesOrderCompleted | System | AR invoice created automatically |

### Business Rules

#### 1. Approval Tiers (Same as AR Invoice)

- **Tier 1 (CFO)**: > $100,000
- **Tier 2 (Finance Mgr)**: > $50,000
- **Tier 3 (Sales Mgr)**: > $10,000 OR first-time customer
- **Auto-approve**: ≤ $10,000 AND established customer

#### 2. Inventory Reservation

**On Approval**:
```php
// Reserve inventory for all order items
foreach ($order->items as $item) {
    Stock::where('product_id', $item->product_id)
        ->where('warehouse_id', $order->warehouse_id)
        ->lockForUpdate()
        ->increment('reserved_quantity', $item->quantity);
}

// Validation
$stock = Stock::where('product_id', $item->product_id)
    ->where('warehouse_id', $warehouse->id)
    ->first();

if ($stock->available_quantity < $item->quantity) {
    throw new InsufficientStockException("Product {$product->name} has only {$stock->available_quantity} available");
}

// available_quantity = quantity - reserved_quantity (generated column)
```

**On Cancellation**:
```php
// Release reservation
foreach ($order->items as $item) {
    Stock::where('product_id', $item->product_id)
        ->where('warehouse_id', $order->warehouse_id)
        ->decrement('reserved_quantity', $item->quantity);
}
```

#### 3. Event-Driven Invoice Creation

**When Order Completed**:
```php
// Warehouse updates status
$order->update(['status' => 'completed']);

// Event fires automatically (Eloquent observer)
event(new SalesOrderCompleted($order));

// Listener processes (SalesOrderCompletedListener)
// 1. Check idempotency
// 2. Validate credit
// 3. Create AR invoice
// 4. Post to GL
// 5. Update order fields
$order->update([
    'ar_invoice_id' => $invoice->id,
    'invoicing_status' => 'fully_invoiced',
    'financial_status' => 'posted'
]);
```

#### 4. Status Fields

**Multiple Status Tracking**:
- `status`: Order processing status (pending → approved → in_progress → completed)
- `invoicing_status`: Invoice creation (not_invoiced → fully_invoiced)
- `financial_status`: GL posting (not_posted → posted → paid)

**Why Multiple Statuses?**
- Separates operational workflow from financial workflow
- Allows tracking of each stage independently
- Better reporting (e.g., "orders completed but not invoiced")

### Database Schema

```sql
status ENUM('pending', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
invoicing_status ENUM('not_invoiced', 'partially_invoiced', 'fully_invoiced') DEFAULT 'not_invoiced',
financial_status ENUM('not_posted', 'posted', 'paid', 'cancelled') DEFAULT 'not_posted',
ar_invoice_id BIGINT UNSIGNED NULL,
approved_by BIGINT UNSIGNED NULL,
approved_at TIMESTAMP NULL
```

---

## 6. Purchase Order Lifecycle

### States Overview

| State | Description | Editable | Cancellable |
|-------|-------------|----------|-------------|
| **pending** | Created, awaiting approval | ✅ Yes | ✅ Yes |
| **approved** | Manager approved | ❌ No | ✅ Yes |
| **ordered** | Sent to supplier | ❌ No | ⚠️ Manager + penalty |
| **received** | Goods in warehouse | ❌ No | ❌ No |
| **completed** | Invoiced (AP created) | ❌ No | ❌ No |
| **cancelled** | Order cancelled | ❌ No | ❌ N/A |

### State Transition Matrix

| From State | To State | Trigger | Actor | Conditions |
|------------|----------|---------|-------|------------|
| **pending** | approved | Approve order | Purchasing Manager | Budget check (optional), approval tier |
| **pending** | cancelled | Cancel order | Purchasing Rep | Before approval |
| **approved** | ordered | Send PO to supplier | Purchasing Rep | Email sent, order_date set |
| **approved** | cancelled | Cancel order | Purchasing Manager | Before sending |
| **ordered** | received | Receive goods | Warehouse | Quality inspection passed |
| **ordered** | cancelled | Cancel order | Purchasing Manager | Supplier penalty may apply |
| **received** | completed | Event: PurchaseOrderReceived | System | AP invoice + inventory movement created |

### Business Rules

#### 1. Approval Tiers

**Lower thresholds than sales orders** (companies control cash outflow more strictly):

- **Tier 1 (CFO)**: > $100,000
- **Tier 2 (Finance Mgr)**: > $50,000
- **Tier 3 (Purchasing Mgr)**: > $5,000 OR new supplier
- **Auto-approve**: ≤ $5,000 AND established supplier

#### 2. Three-Way Match Validation

**Optional per supplier** (configured in supplier record):

**Documents Compared**:
1. Purchase Order (PO)
2. Receiving Report (GRN - Goods Received Note)
3. Supplier Invoice

**Matching Rules**:
```php
// Quantity check
$variance = abs($po->quantity - $grn->quantity) / $po->quantity;
if ($variance > 0.01) { // 1% tolerance
    return ['matched' => false, 'reason' => 'Quantity variance exceeds tolerance'];
}

// Price check
$priceVariance = abs($po->unit_price - $invoice->unit_price) / $po->unit_price;
if ($priceVariance > 0.05) { // 5% tolerance
    return ['matched' => false, 'reason' => 'Price variance exceeds tolerance'];
}

// Amount check
$amountVariance = abs($po->total_amount - $invoice->total_amount);
if ($amountVariance > 0.50) { // $0.50 rounding tolerance
    return ['matched' => false, 'reason' => 'Amount variance exceeds tolerance'];
}

return ['matched' => true];
```

**If Match Fails**:
- AP invoice stays in `pending_approval`
- Discrepancy report generated
- Purchasing manager notified
- Manual review required

#### 3. Receiving Process

**Warehouse Steps**:
1. Physical inspection of goods
2. Count quantities
3. Quality check (if applicable)
4. Create receiving report (GRN)
5. Update PO status to 'received'

**System Actions**:
```php
// Create inventory movement (entry)
InventoryMovement::create([
    'movement_type' => 'entry',
    'warehouse_id' => $po->warehouse_id,
    'product_id' => $item->product_id,
    'quantity' => $item->received_quantity,
    'previous_stock' => $currentStock,
    'new_stock' => $currentStock + $item->received_quantity,
    'unit_cost' => $item->unit_price,
    'reference_type' => 'PurchaseOrder',
    'reference_id' => $po->id
]);

// Update stock
Stock::where('product_id', $item->product_id)
    ->where('warehouse_id', $po->warehouse_id)
    ->increment('quantity', $item->received_quantity);
```

#### 4. Event-Driven Integration

**When Order Received**:
```php
$po->update(['status' => 'received', 'received_date' => now()]);

event(new PurchaseOrderReceived($po));

// Listener processes (PurchaseOrderReceivedListener):
// 1. Check idempotency
// 2. Create AP invoice
// 3. Create inventory movements
// 4. Post to GL
// 5. Update PO fields
$po->update([
    'ap_invoice_id' => $invoice->id,
    'invoicing_status' => 'fully_invoiced',
    'financial_status' => 'posted',
    'status' => 'completed'
]);
```

### Database Schema

```sql
status ENUM('pending', 'approved', 'ordered', 'received', 'completed', 'cancelled') DEFAULT 'pending',
invoicing_status ENUM('not_invoiced', 'partially_invoiced', 'fully_invoiced') DEFAULT 'not_invoiced',
financial_status ENUM('not_posted', 'posted', 'paid', 'cancelled') DEFAULT 'not_posted',
ap_invoice_id BIGINT UNSIGNED NULL,
order_date DATE NULL,
received_date DATE NULL,
approved_by BIGINT UNSIGNED NULL,
approved_at TIMESTAMP NULL
```

---

## 7. Shopping Cart Lifecycle

### States Overview

| State | Description | Actions Allowed | Final |
|-------|-------------|-----------------|-------|
| **active** | Customer shopping | Add/remove items, update quantities | ❌ No |
| **converted** | Became sales order | View only | ✅ Yes |
| **abandoned** | No activity 1+ hour | View, resume shopping | ⚠️ Can expire |
| **expired** | 24-30 days old | View only | ✅ Yes |

### State Transition Matrix

| From State | To State | Trigger | Actor | Conditions |
|------------|----------|---------|-------|------------|
| **active** | converted | Complete checkout | Customer | Payment successful |
| **active** | abandoned | Inactivity | System (scheduled job) | updated_at < (now - 1 hour) |
| **abandoned** | active | Customer returns | Customer | Resume shopping |
| **abandoned** | expired | Old cart cleanup | System (scheduled job) | created_at < (now - 24/30 days) |

### Business Rules

#### 1. Cart Creation

**Guest Carts**:
```php
// Session-based, no login required
$cart = ShoppingCart::create([
    'session_id' => session()->getId(),
    'user_id' => null,
    'status' => 'active'
]);
```

**Logged-In Carts**:
```php
// User-based, persists across sessions
$cart = ShoppingCart::create([
    'session_id' => null,
    'user_id' => auth()->id(),
    'status' => 'active'
]);
```

#### 2. Cart Merge

**When Guest Logs In**:
```php
// Find guest cart
$guestCart = ShoppingCart::where('session_id', session()->getId())
    ->where('status', 'active')
    ->first();

// Find user cart
$userCart = ShoppingCart::where('user_id', auth()->id())
    ->where('status', 'active')
    ->first();

if ($guestCart && $userCart) {
    // Merge: Move guest cart items to user cart
    foreach ($guestCart->items as $item) {
        $existingItem = $userCart->items()
            ->where('product_id', $item->product_id)
            ->first();

        if ($existingItem) {
            // Combine quantities
            $existingItem->increment('quantity', $item->quantity);
        } else {
            // Move item
            $item->update(['cart_id' => $userCart->id]);
        }
    }

    // Delete guest cart
    $guestCart->delete();
}
```

#### 3. Abandoned Cart Workflow

**Detection** (scheduled job runs every hour):
```php
// Mark carts as abandoned
ShoppingCart::where('status', 'active')
    ->where('updated_at', '<', now()->subHour())
    ->update(['status' => 'abandoned']);
```

**Email Reminder** (after 1 hour):
```php
// Only for logged-in users
$abandonedCarts = ShoppingCart::where('status', 'abandoned')
    ->whereNotNull('user_id')
    ->where('updated_at', '>=', now()->subHours(2)) // Within last 2 hours
    ->where('updated_at', '<', now()->subHour())    // But > 1 hour ago
    ->with('user', 'items.product')
    ->get();

foreach ($abandonedCarts as $cart) {
    Mail::to($cart->user->email)->send(new AbandonedCartReminder($cart));
}
```

**Coupon Incentive** (after 24 hours):
```php
// Offer 10% discount to recover cart
$cart->update([
    'recovery_coupon_code' => Coupon::generateCode(),
    'recovery_discount' => 10.00 // 10%
]);

Mail::to($cart->user->email)->send(new AbandonedCartIncentive($cart));
```

#### 4. Cart Expiration

**Scheduled Job** (runs nightly):
```php
// Expire guest carts after 24 hours
ShoppingCart::where('status', 'abandoned')
    ->whereNull('user_id')
    ->where('created_at', '<', now()->subDay())
    ->update(['status' => 'expired']);

// Expire user carts after 30 days
ShoppingCart::where('status', 'abandoned')
    ->whereNotNull('user_id')
    ->where('created_at', '<', now()->subDays(30))
    ->update(['status' => 'expired']);
```

**Cleanup** (after 90 days):
```php
// Delete expired carts and items
ShoppingCart::where('status', 'expired')
    ->where('updated_at', '<', now()->subDays(90))
    ->delete(); // Cascade deletes cart_items
```

#### 5. Cart to Order Conversion

**On Checkout Success**:
```php
DB::transaction(function() use ($cart, $order) {
    // Create sales order
    $order = SalesOrder::create([
        'order_number' => SequenceService::getNextNumber('SO'),
        'contact_id' => $cart->user->contact_id,
        'subtotal' => $cart->subtotal,
        'tax_amount' => $cart->tax,
        'total_amount' => $cart->total,
        'status' => 'pending'
    ]);

    // Copy cart items to order items
    foreach ($cart->items as $cartItem) {
        SalesOrderItem::create([
            'sales_order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'quantity' => $cartItem->quantity,
            'unit_price' => $cartItem->unit_price,
            'line_total' => $cartItem->line_total
        ]);
    }

    // Mark cart as converted
    $cart->update([
        'status' => 'converted',
        'converted_to_order_id' => $order->id
    ]);
});
```

### Database Schema

```sql
status ENUM('active', 'converted', 'abandoned', 'expired') DEFAULT 'active',
session_id VARCHAR(255) NULL,
user_id BIGINT UNSIGNED NULL,
converted_to_order_id BIGINT UNSIGNED NULL,
recovery_coupon_code VARCHAR(50) NULL,
recovery_discount DECIMAL(5,2) DEFAULT 0.00
```

---

## Automatic State Transitions

### Overview

Certain state transitions occur **automatically** without human action, triggered by scheduled jobs or system events.

### 1. Invoice Overdue Detection

**Schedule**: Daily at 00:00

**Code**:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('invoices:check-overdue')->daily();
}
```

**Implementation**:
```php
// app/Console/Commands/CheckOverdueInvoices.php
public function handle()
{
    // AR Invoices
    $overdueAR = ARInvoice::whereIn('status', ['posted', 'partially_paid'])
        ->where('due_date', '<', now()->toDateString())
        ->update(['status' => 'overdue']);

    // AP Invoices
    $overdueAP = APInvoice::whereIn('status', ['posted', 'partially_paid'])
        ->where('due_date', '<', now()->toDateString())
        ->update(['status' => 'overdue']);

    // Send notifications
    foreach (ARInvoice::where('status', 'overdue')->get() as $invoice) {
        Mail::to($invoice->contact->email)->send(new OverdueInvoiceNotification($invoice));
        Mail::to(config('mail.finance_team'))->send(new OverdueInvoiceAlert($invoice));
    }

    $this->info("Marked {$overdueAR} AR invoices and {$overdueAP} AP invoices as overdue");
}
```

### 2. Payment Status Updates

**Trigger**: PaymentApplicationService::applyPayment()

**Code**:
```php
public function applyPayment(int $paymentId, int $invoiceId, float $amount): PaymentApplication
{
    DB::transaction(function() use ($paymentId, $invoiceId, $amount) {
        $payment = Payment::findOrFail($paymentId);
        $invoice = ARInvoice::findOrFail($invoiceId);

        // Create application
        $application = PaymentApplication::create([
            'payment_id' => $paymentId,
            'invoice_type' => 'ar_invoice',
            'invoice_id' => $invoiceId,
            'applied_amount' => $amount
        ]);

        // Update invoice
        $invoice->increment('paid_amount', $amount);

        // AUTOMATIC STATUS UPDATE
        if ($invoice->remaining_balance == 0) {
            $invoice->update([
                'status' => 'paid',
                'paid_date' => now()
            ]);
        } elseif ($invoice->paid_amount > 0) {
            $invoice->update(['status' => 'partially_paid']);
        }

        // Update payment
        $payment->increment('applied_amount', $amount);

        return $application;
    });
}
```

### 3. Order to Invoice Automation

**Trigger**: SalesOrderCompleted event

**Code**:
```php
// app/Listeners/SalesOrderCompletedListener.php
public function handle(SalesOrderCompleted $event)
{
    $order = $event->salesOrder;

    // Idempotency check
    $key = "sales_order_completed_{$order->id}";
    if (IdempotencyKey::where('idempotency_key', $key)->exists()) {
        return; // Already processed
    }

    DB::transaction(function() use ($order, $key) {
        // Create AR invoice
        $invoice = app(ARInvoiceService::class)->createInvoice([
            'contact_id' => $order->contact_id,
            'sales_order_id' => $order->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays($order->payment_terms),
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'status' => 'posted'
        ]);

        // Post to GL (automatic)
        app(AccountingService::class)->postJournalEntry([
            'source_type' => 'ARInvoice',
            'source_id' => $invoice->id,
            // ... journal lines
        ]);

        // AUTOMATIC UPDATE: Order status
        $order->update([
            'ar_invoice_id' => $invoice->id,
            'invoicing_status' => 'fully_invoiced',
            'financial_status' => 'posted'
        ]);

        // Record idempotency
        IdempotencyKey::create([
            'idempotency_key' => $key,
            'request_type' => 'SalesOrderCompleted',
            'request_data' => json_encode($order->toArray())
        ]);
    });
}
```

### 4. Shopping Cart Abandonment

**Schedule**: Hourly

**Code**:
```php
// app/Console/Kernel.php
$schedule->command('carts:check-abandoned')->hourly();
```

**Implementation**:
```php
public function handle()
{
    // Mark as abandoned
    $count = ShoppingCart::where('status', 'active')
        ->where('updated_at', '<', now()->subHour())
        ->update(['status' => 'abandoned']);

    // Send reminders (for logged-in users only)
    $carts = ShoppingCart::where('status', 'abandoned')
        ->whereNotNull('user_id')
        ->where('updated_at', '>=', now()->subHours(2))
        ->where('updated_at', '<', now()->subHour())
        ->with('user', 'items.product')
        ->get();

    foreach ($carts as $cart) {
        Mail::to($cart->user->email)->queue(new AbandonedCartReminder($cart));
    }

    $this->info("Marked {$count} carts as abandoned, sent {$carts->count()} reminders");
}
```

### 5. Shopping Cart Expiration

**Schedule**: Daily at 02:00

**Code**:
```php
$schedule->command('carts:expire-old')->dailyAt('02:00');
```

**Implementation**:
```php
public function handle()
{
    // Expire guest carts (24 hours)
    $guestCount = ShoppingCart::where('status', 'abandoned')
        ->whereNull('user_id')
        ->where('created_at', '<', now()->subDay())
        ->update(['status' => 'expired']);

    // Expire user carts (30 days)
    $userCount = ShoppingCart::where('status', 'abandoned')
        ->whereNotNull('user_id')
        ->where('created_at', '<', now()->subDays(30))
        ->update(['status' => 'expired']);

    // Cleanup very old carts (90 days)
    $deleted = ShoppingCart::where('status', 'expired')
        ->where('updated_at', '<', now()->subDays(90))
        ->delete();

    $this->info("Expired {$guestCount} guest and {$userCount} user carts, deleted {$deleted} old carts");
}
```

---

## Validation Rules

### General Validation Principles

1. **Service Layer Validation**: All state transitions validated in service classes (not controllers)
2. **Exception Throwing**: Invalid transitions throw `ValidationException` with clear message
3. **Transaction Rollback**: Validation failures trigger database rollback
4. **Audit Logging**: All validation failures logged for security monitoring

### Validation Rules by Entity

#### AR/AP Invoices

**Approval Validation**:
```php
// Check approval tier
$approvalCheck = app(ApprovalWorkflowService::class)
    ->requiresARApproval($invoice->total_amount, $invoice->contact_id);

if ($approvalCheck['required']) {
    $requiredApprovers = $approvalCheck['approvers'];

    if (!in_array(auth()->user()->role, $requiredApprovers)) {
        throw new UnauthorizedException(
            "Approval tier {$approvalCheck['tier']} required. Must be: " . implode(', ', $requiredApprovers)
        );
    }
}
```

**Credit Validation**:
```php
$creditCheck = app(CreditManagementService::class)
    ->validateCustomerCredit($invoice->contact_id, $invoice->total_amount);

if (!$creditCheck['approved']) {
    throw new ValidationException(
        "Credit validation failed: {$creditCheck['reason']}"
    );
}
```

**GL Posting Validation**:
```php
// Check period status
$period = FiscalPeriod::findOrFail($invoice->fiscal_period_id);

if ($period->status === 'closed') {
    throw new ValidationException('Cannot post to closed period');
}

// Check invoice not already posted
if ($invoice->status === 'posted') {
    throw new ValidationException('Invoice already posted to GL');
}
```

#### Journal Entries

**Balance Validation**:
```php
if ($entry->total_debit != $entry->total_credit) {
    throw new ValidationException(
        "Journal entry not balanced. Debit: {$entry->total_debit}, Credit: {$entry->total_credit}"
    );
}
```

**Line Validation**:
```php
foreach ($entry->lines as $line) {
    // XOR check: must have debit OR credit, not both
    if (($line->debit_amount > 0 && $line->credit_amount > 0) ||
        ($line->debit_amount == 0 && $line->credit_amount == 0)) {
        throw new ValidationException(
            "Line {$line->line_number} must have either debit or credit, not both or neither"
        );
    }
}

// Minimum lines check
if ($entry->lines->count() < 2) {
    throw new ValidationException('Journal entry must have at least 2 lines');
}
```

**Period Validation**:
```php
app(PeriodControlService::class)->validatePeriodAccess($entry->fiscal_period_id, auth()->id());
// Throws exception if period closed or user lacks override permission
```

#### Orders (Sales/Purchase)

**Inventory Validation** (Sales Order):
```php
foreach ($order->items as $item) {
    $stock = Stock::where('product_id', $item->product_id)
        ->where('warehouse_id', $order->warehouse_id)
        ->first();

    if (!$stock || $stock->available_quantity < $item->quantity) {
        throw new ValidationException(
            "Insufficient stock for {$item->product->name}. Available: {$stock->available_quantity}, Required: {$item->quantity}"
        );
    }
}
```

**Budget Validation** (Purchase Order - optional):
```php
if (config('purchasing.budget_check_enabled')) {
    $budgetCheck = app(BudgetService::class)->checkBudget(
        $order->cost_center_id,
        $order->total_amount,
        $order->fiscal_period_id
    );

    if (!$budgetCheck['approved']) {
        throw new ValidationException(
            "Budget exceeded for cost center {$order->cost_center->name}. Available: {$budgetCheck['available']}, Required: {$order->total_amount}"
        );
    }
}
```

---

## Permission Requirements

### Permission Matrix

| Entity | Transition | Permission | Role |
|--------|------------|------------|------|
| **AR Invoice** | draft → pending_approval | `ar-invoices.submit` | Sales Rep |
| | pending_approval → approved | `ar-invoices.approve` | Sales Manager / Finance Manager / CFO |
| | approved → posted | `ar-invoices.post` | Finance (automatic) |
| | posted → cancelled | `ar-invoices.cancel` | Finance Manager |
| **AP Invoice** | draft → pending_approval | `ap-invoices.submit` | Purchasing Rep |
| | pending_approval → approved | `ap-invoices.approve` | Purchasing Manager / Finance Manager / CFO |
| | approved → posted | `ap-invoices.post` | Finance (automatic) |
| **Journal Entry** | draft → pending_approval | `journal-entries.submit` | Accountant |
| | pending_approval → approved | `journal-entries.approve` | Senior Accountant |
| | approved → posted | `journal-entries.post` | Accountant |
| | posted → reversed | `journal-entries.reverse` | Senior Accountant |
| **Fiscal Period** | open → locked | `fiscal-periods.lock` | Accountant |
| | locked → closed | `fiscal-periods.close` | Senior Accountant |
| | locked → open | `fiscal-periods.unlock` | Senior Accountant |
| | closed → locked | `fiscal-periods.reopen` | CFO |
| | Post to locked period | `override-period-lock` | Senior Accountant / CFO |
| **Sales Order** | pending → approved | `sales-orders.approve` | Sales Manager (tier-based) |
| | approved → in_progress | `sales-orders.process` | Warehouse Manager |
| | in_progress → completed | `sales-orders.complete` | Warehouse Manager |
| | * → cancelled | `sales-orders.cancel` | Sales Manager |
| **Purchase Order** | pending → approved | `purchase-orders.approve` | Purchasing Manager (tier-based) |
| | approved → ordered | `purchase-orders.send` | Purchasing Rep |
| | ordered → received | `purchase-orders.receive` | Warehouse Manager |
| **Shopping Cart** | active → converted | (Customer action) | Customer (auth:sanctum) |

### Role Hierarchy

```
God (Super Admin)
└─ Admin
   ├─ CFO
   │  ├─ Finance Manager
   │  │  └─ Finance
   │  └─ Senior Accountant
   │     └─ Accountant
   ├─ Sales Manager
   │  └─ Sales Rep
   ├─ Purchasing Manager
   │  └─ Purchasing Rep
   └─ Warehouse Manager
      └─ Warehouse
```

### Implementation Example

```php
// app/Policies/ARInvoicePolicy.php
public function approve(User $user, ARInvoice $invoice): bool
{
    // Check approval tier
    $approvalCheck = app(ApprovalWorkflowService::class)
        ->requiresARApproval($invoice->total_amount, $invoice->contact_id);

    if (!$approvalCheck['required']) {
        return true; // Auto-approve
    }

    // Tier 1: CFO required
    if ($approvalCheck['tier'] === 1) {
        return $user->hasRole('CFO');
    }

    // Tier 2: Finance Manager or CFO
    if ($approvalCheck['tier'] === 2) {
        return $user->hasAnyRole(['Finance Manager', 'CFO']);
    }

    // Tier 3: Sales Manager, Finance Manager, or CFO
    if ($approvalCheck['tier'] === 3) {
        return $user->hasAnyRole(['Sales Manager', 'Finance Manager', 'CFO']);
    }

    return false;
}
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-10-28 | Initial comprehensive lifecycle documentation |

---

**Next Steps**:

1. ✅ Review lifecycle diagram in DrawIO
2. ⏳ Complete business rules documentation (Fase 5)

---

**Related Documentation**:

- [C4 Diagrams Guide](C4_DIAGRAMS_GUIDE.md)
- [ERD Documentation](ERD_DOCUMENTATION.md)
- [Business Flows](BUSINESS_FLOWS.md)
- [Performance Optimization Plan](../performance/PERFORMANCE_OPTIMIZATION_PLAN.md)
