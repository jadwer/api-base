# C4 Architecture Diagrams Guide

**Laravel Modular ERP System**
**Date:** 2025-10-28
**Status:** ✅ Production Ready (Phases 1-3 Complete)

---

## 📐 About C4 Model

The C4 model is a hierarchical approach to software architecture diagramming developed by Simon Brown. It provides different levels of abstraction to help understand software systems at various granularities.

**C4 Levels:**
- **Level 1 (Context):** System boundary and external actors
- **Level 2 (Container):** High-level technology choices and containers
- **Level 3 (Component):** Internal structure of containers
- **Level 4 (Code):** Class diagrams (not included in this documentation)

---

## 📁 Available Diagrams

### **L1: System Context** (`L1-system-context.drawio`)

**Purpose:** Shows the ERP system as a single box with external actors and systems.

**Key Elements:**
- **Actors:**
  - Customer (end user using e-commerce and viewing invoices)
  - Admin User (managing products, inventory, orders)
  - Accountant (financial operations, GL posting)

- **External Systems:**
  - Payment Gateway (Stripe, MercadoPago)
  - PAC System (SAT México CFDI stamping)
  - Bank System (statement downloads, balances)

**When to Use:** Executive presentations, high-level system overview, stakeholder communication.

---

### **L2: Container** (`L2-container.drawio`)

**Purpose:** Shows the modular architecture with 7 core business modules, database, and infrastructure.

**Key Elements:**

**Frontend Layer:**
- Frontend Application (Vue.js/React SPA)
- JSON:API Gateway (Laravel 12 with Sanctum auth)

**Business Modules:**
1. **Product Module** (4 entities: Product, Category, Brand, Unit)
2. **Inventory Module** (5 entities: Warehouse, Location, Stock, Batch, Movement)
3. **Sales Module** (2 entities: SalesOrder, SalesOrderItem)
4. **Purchase Module** (2 entities: PurchaseOrder, PurchaseOrderItem)
5. **E-commerce Module** (3 entities: ShoppingCart, CartItem, Coupon)
6. **Finance Module** ⭐ (6 entities, 6 services - Phase 2)
7. **Accounting Module** ⭐ (11 entities, 4 services - Phase 1)

**Supporting Modules:**
- Contacts Module (Party Pattern implementation)
- PermissionManager (role-based access control)

**Infrastructure:**
- Event Bus (Laravel Events for cross-module integration)
- MySQL Database (39+ tables)
- Queue System (Redis/Database for async jobs)
- Cache (Redis for sessions and catalog data)
- Activity Log (Spatie for audit trail)

**Integration Patterns:**
- Event-Driven: Sales/Purchase → Finance
- Direct Service Calls: Finance → Accounting
- Party Pattern: All modules use Contact entity
- Database-level: Foreign key constraints

**When to Use:** Technical architecture reviews, module dependency analysis, deployment planning.

---

### **L3: Component - Finance Module** (`L3-component-finance.drawio`)

**Purpose:** Detailed view of Finance module internal structure.

**Services (6):**

1. **ARInvoiceService**
   - createInvoice() - Creates AR invoice with automatic GL posting
   - updateInvoice() - Updates invoice fields
   - calculateRemainingBalance() - Paid amount - total amount
   - isFullyPaid() - Returns boolean
   - isOverdue() - Checks due_date vs today

2. **APInvoiceService**
   - Same methods as ARInvoiceService
   - Handles supplier invoices (accounts payable)

3. **CreditManagementService** ⭐ (Phase 3)
   - validateCustomerCredit() - Credit limit + overdue + payment score
   - getCurrentARBalance() - Total unpaid AR invoices
   - getOverdueAmount() - Sum of overdue invoices
   - calculatePaymentScore() - (On-time payments / total paid) × 100
   - getCreditAnalysis() - Complete customer credit report
   - getAgingSummary() - 5 aging buckets (current, 1-30, 31-60, 61-90, 90+)

4. **ApprovalWorkflowService** ⭐ (Phase 3)
   - requiresARApproval() - Amount-based tier routing
   - requiresAPApproval() - Amount-based tier routing
   - getRequiredApprovers() - Returns list of approvers by tier
   - approveInvoice() - Records approval in metadata
   - rejectInvoice() - Records rejection with reason

5. **BankReconciliationService** ⭐ (Phase 3)
   - autoReconcile() - 3 matching strategies with confidence scoring
   - markAsReconciled() - Manual reconciliation
   - bulkReconcile() - Batch reconciliation
   - getReconciliationSummary() - Match statistics

6. **PaymentApplicationService**
   - applyPayment() - Links payment to invoice, updates balances
   - unapplyPayment() - Reverses application
   - getApplicationHistory() - Payment application trail

**Models (6):**
- ARInvoice (Accounts Receivable)
- APInvoice (Accounts Payable)
- Payment
- PaymentApplication (linking table)
- BankAccount
- PaymentMethod

**External Dependencies:**
- Accounting Module (for GL posting)
- Contacts Module (Party Pattern for customers/suppliers)
- Sales Module (SalesOrder linkage)
- Purchase Module (PurchaseOrder linkage)
- Event Bus (fires/listens to events)

**When to Use:** Finance module development, credit management configuration, approval workflow setup.

---

### **L3: Component - Accounting Module** (`L3-component-accounting.drawio`)

**Purpose:** Detailed view of Accounting module internal structure.

**Services (4):**

1. **AccountingService**
   - createJournalEntry() - Creates GL entry with lines
   - postJournalEntry() - Posts entry with validation (balance, period, accounts)
   - reverseJournalEntry() - Creates reversal entry with swapped debits/credits
   - approveJournalEntry() - Approval workflow
   - validateBalance() - Debit = Credit within $0.01
   - validatePeriod() - Checks period is open
   - validateAccounts() - All accounts postable and active

2. **PeriodControlService** ⭐ (Phase 3)
   - validatePeriodAccess() - Permission-based access control
   - lockPeriod() - Sets status to 'locked' (soft lock)
   - unlockPeriod() - Reopens locked period
   - closePeriod() - Sets status to 'closed' (hard lock, with validation)
   - reopenPeriod() - Reopens closed period (requires reason)
   - getPeriodStatistics() - Entry counts, totals

3. **AuditTrailService** ⭐ (Phase 3)
   - logFinancialTransaction() - Logs all GL operations
   - logCriticalAction() - Logs to critical_action_logs with SHA256 hash
   - verifyAuditIntegrity() - Hash verification
   - getAuditTrail() - Query audit logs
   - getCriticalActionsLog() - Query critical actions
   - getComplianceReport() - SAT compliance report
   - purgeOldAuditLogs() - Respects 7-15 year retention

4. **SequenceService**
   - getNextSequence() - Concurrent-safe sequence generation
   - lockForUpdate() - MySQL row locking
   - generateNumber() - Formats with prefix/suffix

**Models (11):**
- Account (hierarchical chart of accounts with parent_id)
- JournalEntry (GL entries with status lifecycle)
- JournalLine (entry details with account_id)
- FiscalPeriod (monthly periods with status control)
- Journal (AR, AP, GL types)
- JournalSequence (per-journal, per-fiscal-year sequences)
- ExchangeRate (multi-currency support)
- ExchangeRatePolicy (rate management policies)
- AccountMapping (external system integration)
- AccountBalance (period-end balances)
- IdempotencyKey (duplicate prevention)
- CriticalActionLog (enhanced audit - Phase 3)

**Database Integrity Layer:**

**CHECK Constraints (5):**
1. FiscalPeriod.status IN ('open', 'locked', 'closed')
2. JournalEntry.status IN ('draft', 'approved', 'posted', 'reversed')
3. Account.account_type IN ('asset', 'liability', 'equity', 'revenue', 'expense')
4. Account.nature IN ('debit', 'credit')
5. JournalLine: debit XOR credit (only one can be non-zero)

**MySQL Triggers (4):**
1. journal_lines_after_insert → Update total_debit/total_credit on journal_entries
2. journal_lines_after_update → Update total_debit/total_credit
3. journal_lines_after_delete → Update total_debit/total_credit
4. Auto-balance calculation ensures data integrity at DB level

**When to Use:** Accounting module development, period close procedures, audit compliance configuration.

---

### **L3: Component - Event-Driven Integration** (`L3-component-integration.drawio`)

**Purpose:** Shows cross-module automation flows (Order-to-Cash and Procure-to-Pay).

**Events (4):**

1. **SalesOrderCompleted** (Sales Module)
   - Fired when: SalesOrder.status = 'completed'
   - Payload: salesOrder, orderId, totalAmount, customerId

2. **PurchaseOrderReceived** (Purchase Module)
   - Fired when: PurchaseOrder.status = 'received'
   - Payload: purchaseOrder, orderId, totalAmount, supplierId

3. **ARInvoicePosted** (Finance Module)
   - Fired when: AR Invoice created and GL posted
   - Payload: arInvoice, invoiceId, salesOrderId, journalEntryId

4. **APInvoicePosted** (Finance Module)
   - Fired when: AP Invoice created and GL posted
   - Payload: apInvoice, invoiceId, purchaseOrderId, journalEntryId

**Listeners (4):**

1. **SalesOrderCompletedListener** (Finance Module)
   ```
   handle(SalesOrderCompleted $event):
   1. Check idempotency (ar_invoice_id already set?)
   2. Call ARInvoiceService.createInvoice()
      - Credit validation (if enabled)
      - GL accounts validation (1100, 4100)
      - GL posting automatic
   3. Update sales_orders.ar_invoice_id
   4. Catch exceptions (non-blocking)
   ```

2. **PurchaseOrderReceivedListener** (Finance Module)
   ```
   handle(PurchaseOrderReceived $event):
   1. Check idempotency (ap_invoice_id already set?)
   2. Call APInvoiceService.createInvoice()
      - GL accounts validation (5100, 2100)
      - GL posting automatic
   3. Update purchase_orders.ap_invoice_id
   4. Catch exceptions (non-blocking)
   ```

3. **ARInvoicePostedListener** (Finance Module)
   ```
   handle(ARInvoicePosted $event):
   1. Update SalesOrder:
      - invoicing_status = 'invoiced'
      - financial_status = 'invoiced'
      - invoicing_notes = timestamp
   2. Trigger async credit check (future)
   ```

4. **APInvoicePostedListener** (Finance Module)
   ```
   handle(APInvoicePosted $event):
   1. Update PurchaseOrder:
      - invoicing_status = 'invoiced'
      - financial_status = 'invoiced'
      - invoicing_notes = timestamp
   ```

**Safety Features:**
- ✅ Idempotency checks prevent duplicate invoices
- ✅ Exceptions caught (non-blocking failures)
- ✅ DB transactions for data consistency
- ✅ Comprehensive logging for debugging
- ✅ Event registration in EventServiceProvider

**When to Use:** Event-driven architecture design, automation troubleshooting, integration testing.

---

## 🎨 Diagram Color Legend

### C4 Standard Colors

| Element | Color | Hex Code | Usage |
|---------|-------|----------|-------|
| **Person** | Dark Blue | #08427B | External actors (users) |
| **Software System** | Blue | #1168BD | Our ERP system |
| **Container** | Light Blue | #438DD5 | Modules, API Gateway |
| **Component** | Very Light Blue | #85BBF0 | Services, Models |
| **External System** | Gray | #999999 | Payment Gateways, PAC |

### Custom Colors (This Project)

| Element | Color | Hex Code | Usage |
|---------|-------|----------|-------|
| **Finance Services** | Light Blue | #85BBF0 | ARInvoiceService, etc. |
| **Phase 3 Services** | Light Green | #C7E9C0 | CreditManagement, Approval, BankRecon |
| **Accounting Services** | Green | #C5E1A5 | AccountingService, etc. |
| **Models** | Very Light Blue/Green | #E1F5FE, #E8F5E9 | Eloquent models |
| **Events** | Orange | #FFE082 | Laravel Events |
| **Listeners** | Teal | #B2DFDB | Laravel Listeners |
| **Event Bus** | Yellow | #FFF9C4 | Infrastructure |
| **Database** | Gray | #757575 | MySQL cylinder |
| **Notes/Legend** | Light Yellow | #FFF4E6 | Information boxes |
| **Database Constraints** | Light Orange | #FFF3E0 | CHECK, triggers |

---

## 📖 How to Use These Diagrams

### Opening Diagrams

1. **Online (Recommended):**
   - Go to https://app.diagrams.net/
   - File → Open → Select .drawio file from `docs/architecture/c4/`
   - No installation required

2. **Desktop:**
   - Download diagrams.net desktop app
   - Open .drawio files directly

### Editing Diagrams

All diagrams are fully editable. Common modifications:

**Adding a New Module (L2):**
1. Open `L2-container.drawio`
2. Copy an existing module box
3. Update name, entities, description
4. Add relationships (arrows) to other modules
5. Save

**Adding a New Service (L3):**
1. Open `L3-component-finance.drawio` or `L3-component-accounting.drawio`
2. Copy an existing service component
3. Update methods and description
4. Add relationships to models
5. Save

**Adding a New Event (L3 Integration):**
1. Open `L3-component-integration.drawio`
2. Copy an existing event box
3. Update payload structure
4. Add listener box
5. Connect with arrows (dashed for dispatch, solid for execution)
6. Save

### Diagram Maintenance

**When to Update:**
- ✅ New module added
- ✅ New service created
- ✅ New event/listener added
- ✅ Major architectural change
- ✅ New external system integration

**Update Checklist:**
1. Identify which level(s) need updating (L1, L2, L3)
2. Make changes in diagrams.net
3. Save .drawio files
4. Update this guide if new patterns introduced
5. Commit changes with descriptive message

---

## 🔍 Reading Tips

### L1 - System Context

**Focus on:**
- Who uses the system? (Actors)
- What external systems integrate? (Payment, PAC, Banks)
- What are the main interactions? (Arrows)

**Questions to Answer:**
- What does the system do overall?
- Who are the primary users?
- What external dependencies exist?

### L2 - Container

**Focus on:**
- How is the system divided? (7 modules)
- What technologies are used? (Laravel, MySQL, Redis)
- How do modules communicate? (Events, Direct calls, DB)

**Questions to Answer:**
- What modules make up the system?
- Where does Finance/Accounting fit?
- How does event-driven integration work?
- Where is data stored?

### L3 - Component

**Focus on:**
- What services exist in each module?
- What methods do services provide?
- How do models relate to each other?
- What external dependencies does this module have?

**Questions to Answer:**
- How does credit management work? (Finance L3)
- How are journal entries posted? (Accounting L3)
- What triggers invoice creation? (Integration L3)
- What safety mechanisms exist? (Idempotency, exceptions)

---

## 📊 Architecture Patterns

### 1. Party Pattern (Unified Contact)

**Location:** Contacts Module
**Usage:** All modules (Sales, Purchase, Finance)

```
Contact (is_customer=true) ←→ Customer operations
Contact (is_supplier=true) ←→ Supplier operations
Contact (both flags=true) ←→ Mixed operations
```

**Benefits:**
- Single source of truth for customer/supplier data
- Reduced duplication
- Unified credit management
- Simpler relationships

### 2. Event-Driven Integration

**Location:** Event Bus (Laravel Events)
**Usage:** Sales → Finance, Purchase → Finance

```
SalesOrder.complete()
  → Fires: SalesOrderCompleted
    → Listens: SalesOrderCompletedListener
      → Creates: ARInvoice (automatic)
        → Fires: ARInvoicePosted
          → Listens: ARInvoicePostedListener
            → Updates: SalesOrder.invoicing_status
```

**Benefits:**
- Loose coupling between modules
- Automatic invoice creation
- Status synchronization
- Non-blocking failures (exceptions caught)

### 3. Service Layer Pattern

**Location:** All modules (Finance, Accounting)
**Usage:** Business logic isolation

```
Controller
  → Service (business logic)
    → Model (data access)
      → Database
```

**Benefits:**
- Testable business logic
- Reusable operations
- Clear separation of concerns
- Easy to mock for testing

### 4. Repository Pattern (Implicit via Eloquent)

**Location:** Models
**Usage:** Data access abstraction

```
ARInvoiceService.createInvoice()
  → ARInvoice::create() [Eloquent]
    → Database INSERT
```

**Benefits:**
- ORM benefits (Eloquent)
- Relationship management
- Query builder
- Mass assignment protection

---

## 🚀 Next Steps

### For Developers

1. **Review L2 Container** to understand module boundaries
2. **Review L3 Components** for the module you're working on
3. **Follow service patterns** when adding new business logic
4. **Use event-driven patterns** for cross-module integration

### For Architects

1. **Keep diagrams up-to-date** with system changes
2. **Review L1 Context** when adding external integrations
3. **Update L2 Container** when adding new modules
4. **Document architectural decisions** in this guide

### For Product Managers

1. **Use L1 Context** for stakeholder presentations
2. **Refer to L3 Integration** to understand automation flows
3. **Quote service capabilities** from L3 Component diagrams

---

## 📚 Additional Resources

**C4 Model:**
- Official: https://c4model.com/
- Book: "The C4 model for visualising software architecture" by Simon Brown

**Laravel Documentation:**
- Events: https://laravel.com/docs/12.x/events
- Service Container: https://laravel.com/docs/12.x/container
- Eloquent ORM: https://laravel.com/docs/12.x/eloquent

**Project Documentation:**
- [DATABASE_SCHEMA_REFERENCE.md](../DATABASE_SCHEMA_REFERENCE.md) - Complete database schema
- [PHASE3_COMPLETE.md](../development/PHASE3_COMPLETE.md) - Phase 3 implementation details
- [PROJECT_ACTION_PLAN.md](../../PROJECT_ACTION_PLAN.md) - Project roadmap and status

---

**Last Updated:** 2025-10-28
**Diagram Tool:** diagrams.net (app.diagrams.net)
**Status:** ✅ All diagrams complete and production-ready
