# Finance Module - Frontend Integration Guide

**Module:** Finance
**Entities:** 6 (ARInvoice, APInvoice, Payment, PaymentApplication, BankAccount, PaymentMethod)
**Endpoints:** 30
**Base Path:** `/api/v1`

## Overview

The Finance module manages accounts receivable (AR) and accounts payable (AP) including invoices, payments, and payment applications. It integrates with Sales/Purchase modules for invoice generation and Accounting module for GL posting.

**⚠️ IMPLEMENTATION NOTE:** This documentation reflects the **CURRENT implementation** as of 2025-11-11. Some fields like `paidAmount` are currently writable database fields. Future enhancements will add auto-calculated fields. See `DEVELOPMENT_ROADMAP.md` for planned features.

## Entities

### 1. ARInvoice (Accounts Receivable)

**Endpoint:** `/ar-invoices`
**Resource Type:** `ar-invoices`

#### TypeScript Interface

```typescript
type InvoiceStatus = 'draft' | 'pending' | 'sent' | 'partial' | 'paid' | 'overdue' | 'cancelled' | 'void';

interface ARInvoice {
  id: string;
  invoiceNumber: string;
  invoiceDate: string;
  dueDate: string;
  contactId: number;
  salesOrderId: number | null;
  currency: string;
  subtotal: number;
  taxAmount: number;
  totalAmount: number;

  // Payment tracking (writable fields)
  paidAmount: number;          // ⚠️ Currently writable, not auto-calculated
  paidDate: string | null;

  status: InvoiceStatus;
  journalEntryId: number | null;
  fiscalPeriodId: number | null;

  // Refund/void handling
  isRefund: boolean;
  refundOfInvoiceId: number | null;
  voidedAt: string | null;
  voidedById: number | null;
  voidReason: string | null;

  notes: string | null;
  metadata: Record<string, any> | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;

  // NOTE: Calculate remaining balance on frontend: totalAmount - paidAmount
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Calculated |
|---------------|-----------------|------|----------|----------|------------|
| `invoiceNumber` | `invoice_number` | string | Yes | Yes | No |
| `invoiceDate` | `invoice_date` | date | Yes | Yes | No |
| `dueDate` | `due_date` | date | Yes | Yes | No |
| `contactId` | `contact_id` | number | Yes | Yes | No |
| `salesOrderId` | `sales_order_id` | number | No | Yes | No |
| `currency` | `currency` | string | Yes | Yes | No |
| `subtotal` | `subtotal` | number | Yes | Yes | No |
| `taxAmount` | `tax_amount` | number | Yes | No | No |
| `totalAmount` | `total_amount` | number | Yes | Yes | No |
| `paidAmount` | `paid_amount` | number | Yes | Yes | No |
| `paidDate` | `paid_date` | date | No | Yes | No |
| `status` | `status` | string | Yes | Yes | No |
| `journalEntryId` | `journal_entry_id` | number | No | Yes | No |
| `fiscalPeriodId` | `fiscal_period_id` | number | No | Yes | No |
| `isRefund` | `is_refund` | boolean | No | No | No |
| `refundOfInvoiceId` | `refund_of_invoice_id` | number | No | Yes | No |
| `voidedAt` | `voided_at` | datetime | No | Yes | No |
| `voidedById` | `voided_by_id` | number | No | No | No |
| `voidReason` | `void_reason` | string | No | No | No |

#### Relationships

- `contact` → Contact (belongsTo) - The customer
- `salesOrder` → SalesOrder (belongsTo)
- `journalEntry` → JournalEntry (belongsTo)
- `paymentApplications` → PaymentApplication[] (hasMany)

#### Examples

**Create AR Invoice:**
```javascript
const payload = {
  data: {
    type: "ar-invoices",
    attributes: {
      invoiceNumber: "INV-2025-001",
      invoiceDate: "2025-11-05",
      dueDate: "2025-12-05",
      contactId: 10,
      salesOrderId: 5,
      currency: "USD",
      subtotal: 1000.00,
      taxAmount: 160.00,
      totalAmount: 1160.00,
      status: "pending",
      notes: "Net 30 payment terms"
    }
  }
};

const response = await fetch('/api/v1/ar-invoices', {
  method: 'POST',
  headers,
  body: JSON.stringify(payload)
});
```

**Get Invoice and Calculate Balance:**
```javascript
const response = await fetch(
  '/api/v1/ar-invoices/123?include=contact,paymentApplications',
  { headers }
);

const invoice = await response.json();
const { totalAmount, paidAmount } = invoice.data.attributes;

// Calculate remaining balance on frontend
const remainingBalance = totalAmount - (paidAmount || 0);

console.log({
  totalAmount,
  paidAmount,
  remainingBalance,  // Calculated on frontend
  status: invoice.data.attributes.status
});
```

---

### 2. APInvoice (Accounts Payable)

**Endpoint:** `/ap-invoices`
**Resource Type:** `ap-invoices`

#### TypeScript Interface

```typescript
interface APInvoice {
  id: string;
  invoiceNumber: string;
  invoiceDate: string;
  dueDate: string;
  contactId: number;
  purchaseOrderId: number | null;
  currency: string;
  subtotal: number;
  taxAmount: number;
  totalAmount: number;

  // Payment tracking (writable fields)
  paidAmount: number;          // ⚠️ Currently writable, not auto-calculated
  paidDate: string | null;

  status: InvoiceStatus;
  journalEntryId: number | null;
  fiscalPeriodId: number | null;

  // Refund/void handling
  isRefund: boolean;
  refundOfInvoiceId: number | null;
  voidedAt: string | null;
  voidedById: number | null;
  voidReason: string | null;

  notes: string | null;
  metadata: Record<string, any> | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;

  // NOTE: Calculate remaining balance on frontend: totalAmount - paidAmount
}
```

#### Relationships

- `contact` → Contact (belongsTo) - The supplier
- `purchaseOrder` → PurchaseOrder (belongsTo)
- `journalEntry` → JournalEntry (belongsTo)

---

### 3. Payment

**Endpoint:** `/payments`
**Resource Type:** `payments`

#### TypeScript Interface

```typescript
type PaymentStatus = 'draft' | 'pending' | 'completed' | 'cancelled' | 'void';

interface Payment {
  id: string;
  paymentNumber: string;
  paymentDate: string;
  contactId: number;
  bankAccountId: number;
  paymentMethodId: number;
  amount: number;
  currency: string;

  // Payment application tracking
  appliedAmount: number;
  unappliedAmount: number;

  status: PaymentStatus;
  journalEntryId: number | null;
  reference: string | null;
  notes: string | null;
  metadata: Record<string, any> | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `paymentNumber` | `payment_number` | string | Yes | Yes | Yes |
| `paymentDate` | `payment_date` | date | Yes | Yes | No |
| `contactId` | `contact_id` | number | Yes | Yes | Yes |
| `bankAccountId` | `bank_account_id` | number | Yes | Yes | Yes |
| `paymentMethodId` | `payment_method_id` | number | Yes | Yes | Yes |
| `amount` | `amount` | number | Yes | Yes | No |
| `currency` | `currency` | string | Yes | Yes | Yes |
| `appliedAmount` | `applied_amount` | number | Yes | Yes | No |
| `unappliedAmount` | `unapplied_amount` | number | Yes | Yes | No |
| `status` | `status` | string | Yes | Yes | Yes |
| `reference` | `reference` | string | No | Yes | Yes |

#### Relationships

- `contact` → Contact (belongsTo)
- `bankAccount` → BankAccount (belongsTo)
- `paymentMethod` → PaymentMethod (belongsTo)
- `journalEntry` → JournalEntry (belongsTo)
- `paymentApplications` → PaymentApplication[] (hasMany)

---

### 4. PaymentApplication

**Endpoint:** `/payment-applications`
**Resource Type:** `payment-applications`

#### TypeScript Interface

```typescript
interface PaymentApplication {
  id: string;
  paymentId: number;
  arInvoiceId: number | null;
  apInvoiceId: number | null;
  appliedAmount: number;
  notes: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `payment` → Payment (belongsTo)
- `arInvoice` → ARInvoice (belongsTo)
- `apInvoice` → APInvoice (belongsTo)

---

### 5. BankAccount

**Endpoint:** `/bank-accounts`
**Resource Type:** `bank-accounts`

#### TypeScript Interface

```typescript
interface BankAccount {
  id: string;
  accountName: string;
  accountNumber: string;
  bankName: string;
  currency: string;
  currentBalance: number;
  accountType: string;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}
```

---

### 6. PaymentMethod

**Endpoint:** `/payment-methods`
**Resource Type:** `payment-methods`

#### TypeScript Interface

```typescript
interface PaymentMethod {
  id: string;
  name: string;
  code: string;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}
```

---

## Common Use Cases

### 1. Complete Invoice Payment Flow

```javascript
async function processInvoicePayment(invoiceId, paymentAmount) {
  // 1. Get invoice details and calculate balance
  const invoiceResponse = await fetch(
    `/api/v1/ar-invoices/${invoiceId}?include=paymentApplications`,
    { headers }
  );
  const invoice = await invoiceResponse.json();

  const { totalAmount, paidAmount } = invoice.data.attributes;
  const remainingBalance = totalAmount - (paidAmount || 0);

  console.log('Invoice Balance:', remainingBalance);

  // 2. Create payment
  const paymentPayload = {
    data: {
      type: "payments",
      attributes: {
        paymentNumber: `PAY-${Date.now()}`,
        paymentDate: new Date().toISOString().split('T')[0],
        contactId: invoice.data.attributes.contactId,
        bankAccountId: 1,
        paymentMethodId: 1,
        amount: paymentAmount,
        currency: invoice.data.attributes.currency,
        appliedAmount: 0,
        unappliedAmount: paymentAmount,
        status: "pending",
        reference: `Payment for ${invoice.data.attributes.invoiceNumber}`
      }
    }
  };

  const paymentResponse = await fetch('/api/v1/payments', {
    method: 'POST',
    headers,
    body: JSON.stringify(paymentPayload)
  });

  const payment = await paymentResponse.json();
  const paymentId = payment.data.id;

  // 3. Apply payment to invoice
  const applicationPayload = {
    data: {
      type: "payment-applications",
      attributes: {
        paymentId: parseInt(paymentId),
        arInvoiceId: parseInt(invoiceId),
        appliedAmount: Math.min(paymentAmount, remainingBalance),
        notes: "Invoice payment"
      }
    }
  };

  const applicationResponse = await fetch('/api/v1/payment-applications', {
    method: 'POST',
    headers,
    body: JSON.stringify(applicationPayload)
  });

  // 4. Get updated invoice and calculate new balance
  const updatedInvoice = await fetch(`/api/v1/ar-invoices/${invoiceId}`, { headers });
  const finalInvoice = await updatedInvoice.json();

  const updatedTotalAmount = finalInvoice.data.attributes.totalAmount;
  const updatedPaidAmount = finalInvoice.data.attributes.paidAmount || 0;
  const newBalance = updatedTotalAmount - updatedPaidAmount;

  return {
    payment: payment.data,
    newBalance,
    fullyPaid: newBalance === 0
  };
}
```

### 2. Get Outstanding Invoices

```javascript
async function getOutstandingARInvoices(customerId = null) {
  let url = '/api/v1/ar-invoices?filter[status]=pending&include=contact&sort=-dueDate';

  if (customerId) {
    url += `&filter[contact_id]=${customerId}`;
  }

  const response = await fetch(url, { headers });
  const data = await response.json();

  return data.data.map(invoice => {
    const { totalAmount, paidAmount } = invoice.attributes;
    const remainingBalance = totalAmount - (paidAmount || 0);

    return {
      id: invoice.id,
      invoiceNumber: invoice.attributes.invoiceNumber,
      dueDate: invoice.attributes.dueDate,
      totalAmount,
      remainingBalance,  // Calculated on frontend
      isOverdue: new Date(invoice.attributes.dueDate) < new Date()
    };
  });
}
```

### 3. Apply Partial Payment to Multiple Invoices

```javascript
async function applyPartialPayment(paymentAmount, invoiceIds) {
  // 1. Create payment
  const payment = await createPayment({
    amount: paymentAmount,
    status: "completed"
  });

  let remainingAmount = paymentAmount;

  // 2. Apply to invoices in order
  for (const invoiceId of invoiceIds) {
    if (remainingAmount <= 0) break;

    const invoice = await fetch(`/api/v1/ar-invoices/${invoiceId}`, { headers });
    const invoiceData = await invoice.json();
    const { totalAmount, paidAmount } = invoiceData.data.attributes;
    const balance = totalAmount - (paidAmount || 0);

    const amountToApply = Math.min(remainingAmount, balance);

    await fetch('/api/v1/payment-applications', {
      method: 'POST',
      headers,
      body: JSON.stringify({
        data: {
          type: "payment-applications",
          attributes: {
            paymentId: payment.id,
            arInvoiceId: invoiceId,
            appliedAmount: amountToApply
          }
        }
      })
    });

    remainingAmount -= amountToApply;
  }

  return {
    paymentId: payment.id,
    unappliedAmount: remainingAmount
  };
}
```

### 4. Invoice Aging Report Data

```javascript
async function getInvoiceAgingData() {
  const response = await fetch(
    '/api/v1/ar-invoices?filter[status]=pending&include=contact',
    { headers }
  );

  const invoices = await response.json();
  const today = new Date();

  const aging = {
    current: 0,      // 0-30 days
    days30: 0,       // 31-60 days
    days60: 0,       // 61-90 days
    days90Plus: 0    // 90+ days
  };

  invoices.data.forEach(invoice => {
    const dueDate = new Date(invoice.attributes.dueDate);
    const daysOverdue = Math.floor((today - dueDate) / (1000 * 60 * 60 * 24));
    const { totalAmount, paidAmount } = invoice.attributes;
    const balance = totalAmount - (paidAmount || 0);

    if (daysOverdue <= 30) aging.current += balance;
    else if (daysOverdue <= 60) aging.days30 += balance;
    else if (daysOverdue <= 90) aging.days60 += balance;
    else aging.days90Plus += balance;
  });

  return aging;
}
```

---

## Permissions

### Role-Based Access

| Role | AR Invoice | AP Invoice | Payment | Applications | Bank Accounts |
|------|------------|------------|---------|--------------|---------------|
| **God** | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD |
| **Admin** | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD |
| **Tech** | ✅ Read | ✅ Read | ✅ Read | ✅ Read | ✅ Read |
| **Customer** | ✅ Read (own) | ❌ | ✅ Make | ❌ | ❌ |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/ar-invoices` - List AR invoices
- `GET /api/v1/ap-invoices` - List AP invoices
- `GET /api/v1/payments` - List payments
- `GET /api/v1/payment-applications` - List applications
- `GET /api/v1/bank-accounts` - List bank accounts
- `GET /api/v1/payment-methods` - List payment methods

**Important Frontend Calculations:**
- **remainingBalance**: NOT provided by API - calculate as `totalAmount - paidAmount`
- **paidAmount**: Currently a writable field (future: will be auto-calculated from payment applications)
- For accurate balances, always calculate `remainingBalance` on the frontend

**Related Modules:**
- [Sales Module](SALES_FRONTEND_GUIDE.md) - AR invoice generation from sales orders
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - AP invoice generation from purchase orders
- [Accounting Module](ACCOUNTING_FRONTEND_GUIDE.md) - GL posting for invoices and payments
- [Contacts Module](CONTACTS_FRONTEND_GUIDE.md) - Customer/supplier management
