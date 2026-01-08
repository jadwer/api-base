# Finance Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| ARInvoice | `/api/v1/ar-invoices` | Accounts Receivable |
| APInvoice | `/api/v1/ap-invoices` | Accounts Payable |
| ARPayment | `/api/v1/ar-payments` | Customer payments |
| APPayment | `/api/v1/ap-payments` | Supplier payments |
| BankAccount | `/api/v1/bank-accounts` | Bank accounts |
| BankTransaction | `/api/v1/bank-transactions` | Bank transactions |
| PaymentApplication | `/api/v1/payment-applications` | Payment allocations |

## AR Invoice (Accounts Receivable)

```typescript
type InvoiceStatus = 'draft' | 'posted' | 'partial' | 'paid' | 'void' | 'overdue';

interface ARInvoice {
  id: string;
  invoiceNumber: string;      // Auto: AR-XXXXX
  contactId: number;          // Customer
  salesOrderId: number | null;
  invoiceDate: string;
  dueDate: string;
  currency: string;           // MXN, USD
  subtotal: number;
  taxAmount: number;
  totalAmount: number;

  // Early payment discount
  discountPercent: number | null;    // e.g., 2%
  discountDays: number | null;       // e.g., 10 days
  discountDate: string | null;       // Discount deadline
  discountAmount: number | null;     // Calculated
  discountApplied: boolean;

  // Payment tracking
  paidAmount: number;
  paidDate: string | null;
  status: InvoiceStatus;

  // Accounting
  journalEntryId: number | null;
  fiscalPeriodId: number;

  notes: string | null;
  createdAt: string;
}

// List invoices
GET /api/v1/ar-invoices?filter[status]=posted&include=contact&sort=-invoiceDate

// Create invoice
POST /api/v1/ar-invoices
{
  "data": {
    "type": "ar-invoices",
    "attributes": {
      "contactId": 10,
      "salesOrderId": 5,
      "invoiceDate": "2026-01-08",
      "dueDate": "2026-02-07",
      "subtotal": 10000.00,
      "taxAmount": 1600.00,
      "totalAmount": 11600.00,
      "discountPercent": 2,
      "discountDays": 10
    }
  }
}
```

### Early Payment Discount

```typescript
// Check discount eligibility
GET /api/v1/ar-invoices/{id}

// Response includes
{
  "discountPercent": 2,
  "discountDays": 10,
  "discountDate": "2026-01-18",      // 10 days from invoice
  "discountAmount": 232.00,          // 2% of total
  "discountApplied": false,
  "isDiscountAvailable": true        // Still within discount period
}

// Apply discount when paying early
POST /api/v1/ar-payments
{
  "data": {
    "type": "ar-payments",
    "attributes": {
      "contactId": 10,
      "amount": 11368.00,            // Total - discount
      "applyDiscount": true          // Triggers discount application
    }
  }
}
```

### Late Payment Penalties

```typescript
// Get penalty info for overdue invoice
GET /api/v1/ar-invoices/{id}/late-penalty

// Response
{
  "invoiceId": 1,
  "daysOverdue": 45,
  "originalAmount": 11600.00,
  "penaltyRate": 1.5,                // 1.5% per month
  "penaltyAmount": 261.00,
  "totalWithPenalty": 11861.00
}

// Get all overdue invoices with penalties
GET /api/v1/ar-invoices/overdue-with-penalties

// Apply penalty to invoice
POST /api/v1/ar-invoices/{id}/apply-penalty
{
  "penalty_amount": 261.00,
  "notes": "Late payment penalty - 1.5% monthly"
}

// Get penalty summary
GET /api/v1/ar-invoices/penalty-summary

// Response
{
  "totalOverdue": 5,
  "totalOverdueAmount": 58000.00,
  "totalPenalties": 1305.00,
  "byAging": {
    "1-30": { "count": 2, "amount": 20000.00, "penalty": 300.00 },
    "31-60": { "count": 2, "amount": 25000.00, "penalty": 563.00 },
    "61-90": { "count": 1, "amount": 13000.00, "penalty": 442.00 }
  }
}
```

### Aging Report

```typescript
// Get AR aging report
GET /api/v1/ar-invoices/aging-report

// Response
{
  "asOf": "2026-01-08",
  "summary": {
    "total": 150000.00,
    "current": 50000.00,
    "days1to30": 40000.00,
    "days31to60": 30000.00,
    "days61to90": 20000.00,
    "over90": 10000.00
  },
  "byCustomer": [
    {
      "contactId": 10,
      "name": "Acme Corp",
      "total": 25000.00,
      "current": 10000.00,
      "days1to30": 15000.00,
      // ...
    }
  ]
}
```

## AP Invoice (Accounts Payable)

```typescript
interface APInvoice {
  id: string;
  invoiceNumber: string;      // Supplier invoice number
  contactId: number;          // Supplier
  purchaseOrderId: number | null;
  invoiceDate: string;
  dueDate: string;
  currency: string;
  subtotal: number;
  taxAmount: number;
  totalAmount: number;
  paidAmount: number;
  status: InvoiceStatus;

  // Accounting
  journalEntryId: number | null;
  fiscalPeriodId: number;

  notes: string | null;
  createdAt: string;
}

// List invoices
GET /api/v1/ap-invoices?filter[status]=posted&include=contact&sort=dueDate

// Create invoice
POST /api/v1/ap-invoices
{
  "data": {
    "type": "ap-invoices",
    "attributes": {
      "contactId": 5,
      "purchaseOrderId": 3,
      "invoiceNumber": "SUPP-2026-001",
      "invoiceDate": "2026-01-08",
      "dueDate": "2026-02-07",
      "subtotal": 10000.00,
      "taxAmount": 1600.00,
      "totalAmount": 11600.00
    }
  }
}
```

## Payments

```typescript
type PaymentMethod = 'cash' | 'transfer' | 'check' | 'card' | 'other';

interface ARPayment {
  id: string;
  paymentNumber: string;      // Auto: PAY-XXXXX
  contactId: number;
  amount: number;
  paymentDate: string;
  paymentMethod: PaymentMethod;
  reference: string | null;   // Check number, transfer ref
  bankAccountId: number | null;

  // Accounting
  journalEntryId: number | null;
  fiscalPeriodId: number;

  notes: string | null;
  createdAt: string;
}

// Create payment
POST /api/v1/ar-payments
{
  "data": {
    "type": "ar-payments",
    "attributes": {
      "contactId": 10,
      "amount": 11600.00,
      "paymentDate": "2026-01-08",
      "paymentMethod": "transfer",
      "reference": "TRF-123456",
      "bankAccountId": 1
    }
  }
}

// Apply payment to invoices
POST /api/v1/payment-applications
{
  "data": {
    "type": "payment-applications",
    "attributes": {
      "arPaymentId": 5,
      "arInvoiceId": 10,
      "amount": 11600.00
    }
  }
}
```

### Payment Application Rules

```typescript
// Payments are applied to oldest invoices first (FIFO)
// Cannot apply more than invoice remaining balance
// Invoice status updates automatically:
//   - partial: if payment < remaining
//   - paid: if payment = remaining
```

## Bank Account

```typescript
interface BankAccount {
  id: string;
  name: string;
  bankName: string;
  accountNumber: string;
  accountType: 'checking' | 'savings';
  currency: string;
  currentBalance: number;
  isActive: boolean;

  // Accounting integration
  accountId: number | null;   // GL account

  createdAt: string;
}

// List bank accounts
GET /api/v1/bank-accounts?filter[is_active]=true

// Get account with transactions
GET /api/v1/bank-accounts/{id}?include=transactions
```

## Bank Transaction

```typescript
type TransactionType = 'credit' | 'debit';
type ReconciliationStatus = 'unreconciled' | 'reconciled' | 'matched';

interface BankTransaction {
  id: string;
  bankAccountId: number;
  transactionDate: string;
  amount: number;
  transactionType: TransactionType;
  description: string;
  reference: string | null;
  reconciliationStatus: ReconciliationStatus;
  reconciledAt: string | null;
  reconciledById: number | null;

  createdAt: string;
}

// List transactions
GET /api/v1/bank-transactions?filter[bank_account_id]=1&filter[reconciliation_status]=unreconciled

// Import bank statement (CSV)
POST /api/v1/bank-transactions/import
Content-Type: multipart/form-data
- bank_account_id: 1
- file: [CSV file]

// Auto-reconcile transactions
POST /api/v1/bank-accounts/{id}/auto-reconcile

// Response
{
  "matches": 15,
  "unmatched_bank": 3,
  "unmatched_payments": 2,
  "match_rate": 75
}
```

## Credit Management

```typescript
// Check customer credit status
GET /api/v1/contacts/{id}/credit-status

// Response
{
  "creditLimit": 50000.00,
  "currentBalance": 35000.00,
  "availableCredit": 15000.00,
  "overdueAmount": 5000.00,
  "paymentScore": 85,
  "isOnCreditHold": false,
  "lastPaymentDate": "2026-01-05"
}

// Credit hold is automatic when:
// - paymentScore < 60
// - overdueAmount > 0 for > 60 days
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Credit Hold | Overdue > 60 days blocks new orders | Show warning |
| Early Payment Discount | 2% if paid in 10 days | Show discount deadline |
| Late Penalties | 1.5%/month after due date | Show penalty amount |
| Payment Application | FIFO - oldest invoices first | Show application |
| Auto GL Posting | Invoices/payments post to GL | Transparent |
| Balance Calculation | remaining = total - paid | Use remaining |

## Dashboard Metrics

```typescript
// Get finance dashboard
GET /api/v1/analytics/dashboard

// Response includes
{
  "arSummary": {
    "total": 150000.00,
    "overdue": 25000.00,
    "overduePercent": 16.7
  },
  "apSummary": {
    "total": 80000.00,
    "dueThisWeek": 20000.00
  },
  "cashFlow": {
    "expectedIn": 50000.00,
    "expectedOut": 30000.00,
    "netFlow": 20000.00
  }
}
```
