# COMPLETE API REFERENCE - All Modules

**Base URL:** `http://localhost:8000/api/v1`
**Auth:** Bearer Token (Laravel Sanctum)
**Format:** JSON:API 1.1

**Last Updated:** 2025-10-25

---

## QUICK INDEX

| Module | Resources | Count | Docs |
|--------|-----------|-------|------|
| [Products](#products-module) | products, brands, categories, units | 4 | [Detail](modules/products.md) |
| [Inventory](#inventory-module) | warehouses, locations, stocks, batches, movements | 5 | [Detail](modules/inventory.md) |
| [Sales](#sales-module) | sales-orders, sales-order-items | 2 | [Detail](modules/sales.md) |
| [Purchase](#purchase-module) | purchase-orders, purchase-order-items | 2 | [Detail](modules/purchase.md) |
| [Finance](#finance-module) | ar-invoices, ap-invoices, payments, payment-applications, bank-accounts, payment-methods | 6 | [Detail](modules/finance.md) |
| [Accounting](#accounting-module) | accounts, fiscal-periods, journals, journal-entries, + 8 more | 12 | [Detail](modules/accounting.md) |
| [Contacts](#contacts-module) | contacts, contact-people, contact-addresses, contact-documents | 4 | [Detail](modules/contacts.md) |
| [Ecommerce](#ecommerce-module) | shopping-carts, cart-items, coupons | 3 | [Detail](modules/ecommerce.md) |
| [Auth/Admin](#authadmin) | users, roles, permissions, pages, audits | 5 | System |

**Total Endpoints:** ~230 routes across 43 resources

---

## PRODUCTS MODULE

### Resources (4)

#### Products
```
GET    /api/v1/products
POST   /api/v1/products
GET    /api/v1/products/{id}
PATCH  /api/v1/products/{id}
DELETE /api/v1/products/{id}
```

**Attributes:** name, sku, description, price, cost, iva, imgPath, datasheetPath
**Relationships:** unit, category, brand
**Filters:** name, sku, category, brand
**Includes:** unit, category, brand

#### Brands
```
GET    /api/v1/brands
POST   /api/v1/brands
GET    /api/v1/brands/{id}
PATCH  /api/v1/brands/{id}
DELETE /api/v1/brands/{id}
```

**Attributes:** name, description
**Relationships:** products
**Filters:** name

#### Categories
```
GET    /api/v1/categories
POST   /api/v1/categories
GET    /api/v1/categories/{id}
PATCH  /api/v1/categories/{id}
DELETE /api/v1/categories/{id}
```

**Attributes:** name, description, parentId
**Relationships:** parent, children, products
**Filters:** name, parentId

#### Units
```
GET    /api/v1/units
POST   /api/v1/units
GET    /api/v1/units/{id}
PATCH  /api/v1/units/{id}
DELETE /api/v1/units/{id}
```

**Attributes:** name, abbreviation, conversionFactor
**Relationships:** products

---

## INVENTORY MODULE

### Resources (5)

#### Warehouses
```
GET    /api/v1/warehouses
POST   /api/v1/warehouses
GET    /api/v1/warehouses/{id}
PATCH  /api/v1/warehouses/{id}
DELETE /api/v1/warehouses/{id}
```

**Attributes:** code, name, address, type, isActive
**Relationships:** locations, stocks, movements
**Filters:** code, name, type, isActive

#### Warehouse Locations
```
GET    /api/v1/warehouse-locations
POST   /api/v1/warehouse-locations
GET    /api/v1/warehouse-locations/{id}
PATCH  /api/v1/warehouse-locations/{id}
DELETE /api/v1/warehouse-locations/{id}
```

**Attributes:** warehouseId, code, name, aisle, rack, shelf, bin, isActive
**Relationships:** warehouse, stocks
**Filters:** warehouseId, code, isActive

#### Stocks
```
GET    /api/v1/stocks
POST   /api/v1/stocks
GET    /api/v1/stocks/{id}
PATCH  /api/v1/stocks/{id}
DELETE /api/v1/stocks/{id}
```

**Attributes:** productId, warehouseId, locationId, batchId, quantity, reservedQuantity, minLevel, maxLevel
**Relationships:** product, warehouse, location, batch
**Filters:** productId, warehouseId, locationId, batchId
**Calculated:** availableQuantity

#### Product Batches
```
GET    /api/v1/product-batches
POST   /api/v1/product-batches
GET    /api/v1/product-batches/{id}
PATCH  /api/v1/product-batches/{id}
DELETE /api/v1/product-batches/{id}
```

**Attributes:** productId, batchNumber, manufactureDate, expiryDate, quantity, supplier
**Relationships:** product, stocks
**Filters:** productId, batchNumber, expiryDate

#### Inventory Movements
```
GET    /api/v1/inventory-movements
POST   /api/v1/inventory-movements
GET    /api/v1/inventory-movements/{id}
PATCH  /api/v1/inventory-movements/{id}
DELETE /api/v1/inventory-movements/{id}
```

**Attributes:** movementType, productId, fromWarehouseId, toWarehouseId, quantity, date, reference, notes
**Relationships:** product, fromWarehouse, toWarehouse, batch
**Filters:** movementType, productId, warehouseId, date
**Movement Types:** in, out, transfer, adjustment

---

## SALES MODULE

### Resources (2)

#### Sales Orders
```
GET    /api/v1/sales-orders
POST   /api/v1/sales-orders
GET    /api/v1/sales-orders/{id}
PATCH  /api/v1/sales-orders/{id}
DELETE /api/v1/sales-orders/{id}
```

**Attributes:** orderNumber, customerId, orderDate, status, subtotal, tax, total, notes
**Relationships:** customer (Contact), items
**Filters:** customerId, status, orderDate
**Includes:** customer, items
**Status:** pending, confirmed, processing, shipped, delivered, cancelled

#### Sales Order Items
```
GET    /api/v1/sales-order-items
POST   /api/v1/sales-order-items
GET    /api/v1/sales-order-items/{id}
PATCH  /api/v1/sales-order-items/{id}
DELETE /api/v1/sales-order-items/{id}
```

**Attributes:** salesOrderId, productId, quantity, unitPrice, discount, subtotal
**Relationships:** salesOrder, product
**Filters:** salesOrderId, productId

---

## PURCHASE MODULE

### Resources (2)

#### Purchase Orders
```
GET    /api/v1/purchase-orders
POST   /api/v1/purchase-orders
GET    /api/v1/purchase-orders/{id}
PATCH  /api/v1/purchase-orders/{id}
DELETE /api/v1/purchase-orders/{id}
```

**Attributes:** orderNumber, supplierId, orderDate, deliveryDate, status, subtotal, tax, total, notes
**Relationships:** supplier (Contact), items
**Filters:** supplierId, status, orderDate
**Includes:** supplier, items
**Status:** draft, sent, confirmed, received, cancelled

#### Purchase Order Items
```
GET    /api/v1/purchase-order-items
POST   /api/v1/purchase-order-items
GET    /api/v1/purchase-order-items/{id}
PATCH  /api/v1/purchase-order-items/{id}
DELETE /api/v1/purchase-order-items/{id}
```

**Attributes:** purchaseOrderId, productId, quantity, unitPrice, receivedQuantity, subtotal
**Relationships:** purchaseOrder, product
**Filters:** purchaseOrderId, productId

---

## FINANCE MODULE

### Resources (6)

#### AR Invoices (Accounts Receivable)
```
GET    /api/v1/ar-invoices
POST   /api/v1/ar-invoices
GET    /api/v1/ar-invoices/{id}
PATCH  /api/v1/ar-invoices/{id}
DELETE /api/v1/ar-invoices/{id}
```

**Attributes:** invoiceNumber, invoiceDate, dueDate, customerId, currency, subtotal, taxAmount, totalAmount, paidAmount, status, journalEntryId, notes, isActive
**Relationships:** customer (Contact), journalEntry, paymentApplications
**Filters:** customerId, status, invoiceDate, dueDate
**Includes:** customer, journalEntry, paymentApplications
**Status:** draft, posted, partial, paid, overdue, cancelled
**Calculated:** paidAmount, remainingBalance

#### AP Invoices (Accounts Payable)
```
GET    /api/v1/ap-invoices
POST   /api/v1/ap-invoices
GET    /api/v1/ap-invoices/{id}
PATCH  /api/v1/ap-invoices/{id}
DELETE /api/v1/ap-invoices/{id}
```

**Attributes:** invoiceNumber, invoiceDate, dueDate, supplierId, currency, subtotal, taxAmount, totalAmount, paidAmount, status, journalEntryId, notes, isActive
**Relationships:** supplier (Contact), journalEntry
**Filters:** supplierId, status, invoiceDate, dueDate
**Includes:** supplier, journalEntry
**Status:** draft, posted, partial, paid, overdue, cancelled
**Calculated:** paidAmount, remainingBalance

#### Payments (UNIFIED - replaces old a-p-payments and a-r-receipts)
```
GET    /api/v1/payments
POST   /api/v1/payments
GET    /api/v1/payments/{id}
PATCH  /api/v1/payments/{id}
DELETE /api/v1/payments/{id}
```

**Attributes:** paymentNumber, paymentDate, customerId, bankAccountId, paymentMethodId, amount, currency, appliedAmount, unappliedAmount, status, journalEntryId, reference, notes, isActive
**Relationships:** customer (Contact), bankAccount, paymentMethod, journalEntry, paymentApplications
**Filters:** customerId, bankAccountId, status, paymentDate
**Includes:** customer, bankAccount, paymentMethod, journalEntry, paymentApplications
**Status:** unapplied, partial, applied
**Calculated:** appliedAmount, unappliedAmount

#### Payment Applications (NEW)
```
GET    /api/v1/payment-applications
POST   /api/v1/payment-applications
GET    /api/v1/payment-applications/{id}
PATCH  /api/v1/payment-applications/{id}
DELETE /api/v1/payment-applications/{id}
```

**Attributes:** paymentId, arInvoiceId, amount, applicationDate, notes, isActive
**Relationships:** payment, arInvoice
**Filters:** paymentId, arInvoiceId
**Includes:** payment, arInvoice

#### Bank Accounts
```
GET    /api/v1/bank-accounts
POST   /api/v1/bank-accounts
GET    /api/v1/bank-accounts/{id}
PATCH  /api/v1/bank-accounts/{id}
DELETE /api/v1/bank-accounts/{id}
```

**Attributes:** accountNumber, accountName, bankName, currency, glAccountId, currentBalance, openingBalance, status, isActive
**Relationships:** glAccount (Account from Accounting)
**Filters:** bankName, currency, status
**Includes:** glAccount

#### Payment Methods
```
GET    /api/v1/payment-methods
POST   /api/v1/payment-methods
GET    /api/v1/payment-methods/{id}
PATCH  /api/v1/payment-methods/{id}
DELETE /api/v1/payment-methods/{id}
```

**Attributes:** code, name, type, requiresReference, isActive
**Filters:** type, isActive

---

## ACCOUNTING MODULE

### Resources (12)

#### Accounts (Chart of Accounts / Plan Contable)
```
GET    /api/v1/accounts
POST   /api/v1/accounts
GET    /api/v1/accounts/{id}
PATCH  /api/v1/accounts/{id}
DELETE /api/v1/accounts/{id}
```

**Attributes:** code, name, accountType, nature, level, parentId, currency, isPostable, isCashFlow, status
**Relationships:** parent, children, balances
**Filters:** code, accountType, nature, level, isPostable, status
**Includes:** parent, children, balances

#### Account Balances
```
GET    /api/v1/account-balances
POST   /api/v1/account-balances
GET    /api/v1/account-balances/{id}
PATCH  /api/v1/account-balances/{id}
DELETE /api/v1/account-balances/{id}
```

**Attributes:** accountId, fiscalPeriodId, fiscalYear, fiscalMonth, openingBalance, periodDebits, periodCredits, closingBalance
**Relationships:** account, fiscalPeriod
**Filters:** accountId, fiscalPeriodId, fiscalYear, fiscalMonth
**Calculated:** closingBalance

#### Fiscal Periods
```
GET    /api/v1/fiscal-periods
POST   /api/v1/fiscal-periods
GET    /api/v1/fiscal-periods/{id}
PATCH  /api/v1/fiscal-periods/{id}
DELETE /api/v1/fiscal-periods/{id}
```

**Attributes:** year, month, name, startDate, endDate, status, closedAt, closedById, closingEntryId
**Relationships:** closedBy (User), closingEntry (JournalEntry), balances
**Filters:** year, month, status
**Status:** open, closed

#### Journals
```
GET    /api/v1/journals
POST   /api/v1/journals
GET    /api/v1/journals/{id}
PATCH  /api/v1/journals/{id}
DELETE /api/v1/journals/{id}
```

**Attributes:** code, name, description, prefix, type, sequenceId, status
**Relationships:** sequence, entries
**Filters:** code, type, status
**Types:** general, sales, purchases, cash, bank

#### Journal Entries
```
GET    /api/v1/journal-entries
POST   /api/v1/journal-entries
GET    /api/v1/journal-entries/{id}
PATCH  /api/v1/journal-entries/{id}
DELETE /api/v1/journal-entries/{id}
```

**Attributes:** fiscalPeriodId, journalId, number, date, description, reference, status, totalDebit, totalCredit, postedAt, postedById, reversedAt, reversedById, reversingEntryId
**Relationships:** fiscalPeriod, journal, journalLines, postedBy, reversedBy, reversingEntry
**Filters:** journalId, fiscalPeriodId, status, date
**Includes:** journal, fiscalPeriod, journalLines, postedBy
**Status:** draft, posted, reversed
**Calculated:** totalDebit, totalCredit

#### Journal Lines
```
GET    /api/v1/journal-lines
POST   /api/v1/journal-lines
GET    /api/v1/journal-lines/{id}
PATCH  /api/v1/journal-lines/{id}
DELETE /api/v1/journal-lines/{id}
```

**Attributes:** journalEntryId, accountId, lineNumber, debit, credit, description
**Relationships:** journalEntry, account
**Filters:** journalEntryId, accountId

#### + 6 More Internal Resources
- journal-sequences (Sequence number generation)
- account-mappings (Automatic GL account mapping)
- exchange-rates (Multi-currency support)
- exchange-rate-policies (Exchange rate policies)
- audit-logs (Accounting audit trail)
- idempotency-keys (API idempotency)

---

## CONTACTS MODULE

### Resources (4)

#### Contacts
```
GET    /api/v1/contacts
POST   /api/v1/contacts
GET    /api/v1/contacts/{id}
PATCH  /api/v1/contacts/{id}
DELETE /api/v1/contacts/{id}
```

**Attributes:** name, email, phone, taxId, isCustomer, isSupplier, isActive, notes
**Relationships:** people, addresses, documents, salesOrders, purchaseOrders, arInvoices, apInvoices
**Filters:** name, email, isCustomer, isSupplier, isActive
**Includes:** people, addresses, documents

#### Contact People
```
GET    /api/v1/contact-people
POST   /api/v1/contact-people
GET    /api/v1/contact-people/{id}
PATCH  /api/v1/contact-people/{id}
DELETE /api/v1/contact-people/{id}
```

**Attributes:** contactId, firstName, lastName, position, email, phone, isActive
**Relationships:** contact
**Filters:** contactId, isActive

#### Contact Addresses
```
GET    /api/v1/contact-addresses
POST   /api/v1/contact-addresses
GET    /api/v1/contact-addresses/{id}
PATCH  /api/v1/contact-addresses/{id}
DELETE /api/v1/contact-addresses/{id}
```

**Attributes:** contactId, type, street, city, state, postalCode, country, isDefault
**Relationships:** contact
**Filters:** contactId, type, isDefault

#### Contact Documents
```
GET    /api/v1/contact-documents
POST   /api/v1/contact-documents
GET    /api/v1/contact-documents/{id}
PATCH  /api/v1/contact-documents/{id}
DELETE /api/v1/contact-documents/{id}
```

**Attributes:** contactId, documentType, fileName, filePath, fileSize, expiryDate, notes
**Relationships:** contact
**Filters:** contactId, documentType, expiryDate

---

## ECOMMERCE MODULE

### Resources (3)

#### Shopping Carts
```
GET    /api/v1/shopping-carts
POST   /api/v1/shopping-carts
GET    /api/v1/shopping-carts/{id}
PATCH  /api/v1/shopping-carts/{id}
DELETE /api/v1/shopping-carts/{id}
```

**Attributes:** userId, status, subtotal, tax, total, couponId, discount
**Relationships:** user, items, coupon
**Filters:** userId, status
**Includes:** items, coupon
**Status:** active, converted, abandoned

#### Cart Items
```
GET    /api/v1/cart-items
POST   /api/v1/cart-items
GET    /api/v1/cart-items/{id}
PATCH  /api/v1/cart-items/{id}
DELETE /api/v1/cart-items/{id}
```

**Attributes:** shoppingCartId, productId, quantity, unitPrice, subtotal
**Relationships:** shoppingCart, product
**Filters:** shoppingCartId, productId

#### Coupons
```
GET    /api/v1/coupons
POST   /api/v1/coupons
GET    /api/v1/coupons/{id}
PATCH  /api/v1/coupons/{id}
DELETE /api/v1/coupons/{id}
```

**Attributes:** code, description, discountType, discountValue, minPurchaseAmount, startDate, endDate, usageLimit, usageCount, isActive
**Relationships:** carts
**Filters:** code, isActive, startDate, endDate
**Discount Types:** percentage, fixed

---

## AUTH/ADMIN

### Resources (5)

- **users** - User management
- **roles** - Role-based access control
- **permissions** - Granular permissions
- **pages** - CMS pages (PageBuilder)
- **audits** - System audit log

---

## COMMON PATTERNS

### Authentication
```bash
# All endpoints require authentication
Authorization: Bearer {token}
```

### Pagination
```
GET /api/v1/products?page[number]=1&page[size]=15
```

### Filtering
```
GET /api/v1/products?filter[name]=laptop&filter[brand]=5
```

### Sorting
```
GET /api/v1/products?sort=-created_at,name
```

### Including Relationships
```
GET /api/v1/ar-invoices?include=customer,journalEntry,paymentApplications
```

### Error Responses
```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "The invoice number field is required.",
      "source": { "pointer": "/data/attributes/invoiceNumber" }
    }
  ]
}
```

---

## BREAKING CHANGES HISTORY

### 2025-10-24
- **Finance Module:** Renamed resources
  - `a-p-invoices` → `ap-invoices`
  - `a-r-invoices` → `ar-invoices`
  - `a-p-payments` → REMOVED (use `payments`)
  - `a-r-receipts` → REMOVED (use `payments`)
- **Finance Module:** New resources
  - Added `payments` (unified AR/AP payments)
  - Added `payment-applications` (track payment-to-invoice mapping)
- **Finance Module:** Field changes
  - ARInvoice: `contactId` → `customerId`
  - APInvoice: `contactId` → `supplierId`

---

**For detailed module documentation, see individual files in `modules/` folder.**

**Last Updated:** 2025-10-25
