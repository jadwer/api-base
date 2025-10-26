# API CHANGELOG

**Purpose:** Track all API changes for Frontend awareness
**Format:** Keep-a-Changelog compatible

---

## [Unreleased]

### Added
- Complete API documentation in `/docs/api-documentation/`
- 43 resources across 8 modules fully documented

---

## [2025-10-25] - Finance Module Restructure

### 🔴 BREAKING CHANGES

#### URL Changes
- **Changed:** `/api/v1/a-p-invoices` → `/api/v1/ap-invoices`
- **Changed:** `/api/v1/a-r-invoices` → `/api/v1/ar-invoices`
- **Removed:** `/api/v1/a-p-payments` (use `/api/v1/payments` instead)
- **Removed:** `/api/v1/a-r-receipts` (use `/api/v1/payments` instead)

#### Resource Type Changes
- **Changed:** Resource type `"a-p-invoices"` → `"ap-invoices"`
- **Changed:** Resource type `"a-r-invoices"` → `"ar-invoices"`
- **Removed:** Resource type `"a-p-payments"`
- **Removed:** Resource type `"a-r-receipts"`

#### Field Name Changes
- **AR Invoice:** `contactId` → `customerId`
- **AP Invoice:** `contactId` → `supplierId`

### Added

#### New Resources
- `/api/v1/payments` - Unified payment resource (replaces a-p-payments and a-r-receipts)
- `/api/v1/payment-applications` - Track payment-to-invoice applications

#### New Fields
**Payment (New Resource):**
- `paymentNumber` (string)
- `paymentDate` (date)
- `customerId` (integer) - For AR payments
- `bankAccountId` (integer)
- `paymentMethodId` (integer)
- `amount` (numeric)
- `currency` (string)
- `appliedAmount` (numeric) - Calculated
- `unappliedAmount` (numeric) - Calculated
- `status` (enum: unapplied, partial, applied)
- `journalEntryId` (integer|null)
- `reference` (string|null)
- `notes` (text|null)
- `isActive` (boolean)

**Payment Application (New Resource):**
- `paymentId` (integer)
- `arInvoiceId` (integer)
- `amount` (numeric)
- `applicationDate` (date)
- `notes` (text|null)
- `isActive` (boolean)

**AR/AP Invoices - Calculated Fields:**
- `paidAmount` (numeric) - Auto-calculated from payment applications
- `remainingBalance` (numeric) - Calculated: totalAmount - paidAmount

### Changed
- **Finance schemas:** All field validations now use correct types (integer/numeric instead of string)
- **Finance includes:** Enhanced relationship support for new payment tracking

### Migration Guide

**Old Code (Frontend):**
```typescript
// ❌ OLD
const response = await axios.get('/api/v1/a-p-invoices');
const data = response.data.data.map(invoice => ({
  ...invoice.attributes,
  contactId: invoice.attributes.contactId
}));
```

**New Code (Frontend):**
```typescript
// ✅ NEW
const response = await axios.get('/api/v1/ap-invoices');
const data = response.data.data.map(invoice => ({
  ...invoice.attributes,
  supplierId: invoice.attributes.supplierId  // Changed field name
}));
```

---

## [2025-10-24] - Accounting Module Field Mapping Fix

### Fixed
- **Accounting Requests:** Corrected validation types
  - Foreign keys: `'string'` → `'integer'`
  - `companyId`: `'required'` → `'nullable'`
  - Numeric fields: `'string'` → `'numeric'`
  - Corrected camelCase field names

### Changed
- **Accounting Schemas:** Added explicit column mapping for all fields
  - Pattern: `Field::make('camelCase', 'snake_case')`

**Affected Resources:**
- accounts
- account-balances
- account-mappings
- fiscal-periods
- journals
- journal-entries
- journal-lines
- journal-sequences
- exchange-rates
- exchange-rate-policies
- audit-logs
- idempotency-keys

---

## [2025-08-20] - Finance & Accounting Phase 1 Launch

### Added
- **Finance Module:** 6 resources
  - ar-invoices (with old naming)
  - ap-invoices (with old naming)
  - a-p-payments (deprecated)
  - a-r-receipts (deprecated)
  - bank-accounts
  - payment-methods

- **Accounting Module:** 12 resources
  - accounts (Plan Contable)
  - account-balances
  - account-mappings
  - fiscal-periods
  - journals
  - journal-entries
  - journal-lines
  - journal-sequences
  - exchange-rates
  - exchange-rate-policies
  - audit-logs
  - idempotency-keys

### Features
- GL Posting automático
- Calculated fields (paidAmount, remainingBalance)
- Spanish validation messages
- JSON:API 1.1 compliance

---

## [2025-08-15] - Complete Module Launch

### Added
- **Products Module:** products, brands, categories, units
- **Inventory Module:** warehouses, locations, stocks, batches, movements
- **Sales Module:** sales-orders, sales-order-items
- **Purchase Module:** purchase-orders, purchase-order-items
- **Contacts Module:** contacts, contact-people, contact-addresses, contact-documents
- **Ecommerce Module:** shopping-carts, cart-items, coupons

---

## JSON:API Standards

### Followed Conventions
- **URLs:** kebab-case (`ar-invoices`, `bank-accounts`)
- **Resource Types:** kebab-case (`"ar-invoices"`, `"bank-accounts"`)
- **Attributes:** camelCase (`invoiceNumber`, `totalAmount`)
- **Relationships:** camelCase (`customer`, `journalEntry`)
- **Database:** snake_case (`invoice_number`, `total_amount`)

### Response Format
```json
{
  "jsonapi": { "version": "1.0" },
  "data": { ... },
  "included": [ ... ],
  "meta": { "page": { ... } },
  "links": { ... }
}
```

---

## Breaking Change Policy

1. **Deprecation Notice:** 2 weeks minimum before removal
2. **Documentation:** Update BREAKING_CHANGES.md immediately
3. **Frontend Notice:** Notify team via this changelog
4. **Migration Guide:** Provide code examples
5. **Support:** Both versions run in parallel during migration period (if possible)

---

## Versioning

**Current Version:** v1 (all endpoints under `/api/v1/`)

**Future:** When breaking changes accumulate, will release v2 (`/api/v2/`) while maintaining v1 for compatibility.

---

**For detailed endpoint documentation, see [COMPLETE_API_REFERENCE.md](COMPLETE_API_REFERENCE.md)**

**Last Updated:** 2025-10-25
