# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## CRITICAL RULES

### Git Commits
- **NEVER make commits automatically** - Always provide commit message text for manual execution
- NO emojis in commit messages
- NO "Generated with Claude Code" credits
- NO "Co-Authored-By: Claude" footers

### Module Policy
- **NEVER regenerate modules** unless explicitly requested
- Prefer modification over regeneration for working modules

---

## Project Status

**Version:** v1.0 (2026-01-06)
**Production Readiness:** 100%

### Modules (14/14 Complete)

| Module | Entities | Tests |
|--------|----------|-------|
| Product | Products, Units, Categories, Brands, Variants | 26 |
| Inventory | Warehouses, Locations, Stock, Batches, Movements, CycleCounts | 24 |
| Purchase | Suppliers, Orders, Items, Approval, Budgets | 18 |
| Sales | Orders, Items, Shipments, Backorders, DiscountRules | 24 |
| Ecommerce | Carts, Checkout, Payments, Wishlists, Reviews, Recommendations | 64 |
| Finance | AP/AR Invoices, Payments, Bank Accounts, EarlyPaymentDiscount | 39 |
| Accounting | Accounts, Journal Entries, Fiscal Periods, Exchange Rates | 61 |
| Reports | Financial Statements, Management Reports, KPIs | 50 |
| HR | Employees, Attendance, Payroll, Leave, Performance | 45 |
| CRM | Pipeline, Leads, Campaigns, Activities, Opportunities | 25 |
| Billing | CFDI, PAC (SW Sapien), Stripe | 25 |
| Contacts | Contacts, Addresses, Documents, Duplicate Detection | 20 |
| Audit | Activity Logging | 3 |
| SystemHealth | Health Checks | 1 |

### Metrics
- **Models/Entities:** 85+
- **API Endpoints:** 736+
- **Tests (files):** 452
- **Business Rules:** 175/175 (100%)
- **API Docs:** Scribe (664 endpoints)

### v1.1 Features (All Complete)
- PU-M003 Budget Control
- IV-M001 Cycle Count Scheduling
- CO-M001 Duplicate Detection
- SA-M003 Automatic Discount Rules
- FI-M002 Early Payment Discount
- E2E Integration Tests
- Stripe Payment Gateway

---

## Key Documentation

| Document | Purpose |
|----------|---------|
| `docs/DATABASE_SCHEMA_REFERENCE.md` | **READ FIRST** - Database schema |
| `docs/DEVELOPMENT_ROADMAP.md` | Current status and pending tasks |
| `docs/FRONTEND_INTEGRATION_GUIDE.md` | API integration for frontend |
| `docs/architecture/README.md` | System architecture |
| `docs/development/MODULE_IMPLEMENTATION_METHODOLOGY.md` | How to create modules |
| `docs/modules/*_FRONTEND_GUIDE.md` | Per-module integration guides |

---

## Tech Stack

- **Framework:** Laravel 12
- **API:** JSON:API 5.x (laravel-json-api/laravel)
- **Modules:** nwidart/laravel-modules
- **Auth:** Laravel Sanctum + Spatie Permission
- **Testing:** PHPUnit (SQLite for speed)
- **Audit:** Spatie Activity Log
- **Payments:** Stripe
- **CFDI:** SW Sapien PAC

---

## Common Commands

```bash
# Run all tests
php artisan test

# Run module tests
php artisan test Modules/{Module}/

# Run specific test file
php artisan test Modules/Ecommerce/tests/Integration/OnlineSalesE2ETest.php

# Fresh database
php artisan migrate:fresh --seed

# Create module
php artisan module:advanced-blueprint {Name} --entities="Entity1,Entity2"

# Generate API docs
php artisan scribe:generate
```

---

## Test Fix Workflow

When a test fails, check in this order:

```
1. Model      -> fillable, casts, relationships
2. Migration  -> columns match model
3. Schema     -> fields(), filters(), pagination()
4. Request    -> validation rules
5. Authorizer -> 10 methods, permissions (plural)
6. Factory    -> generates valid data
7. Seed       -> permissions created
8. Routes     -> resource registered
9. Server.php -> schema + authorizer uncommented
10. Tests     -> data format matches schema
```

---

## Naming Conventions

| Context | Convention | Example |
|---------|------------|---------|
| Models | Singular PascalCase | `SalesOrder` |
| Tables | Plural snake_case | `sales_orders` |
| Endpoints | Plural kebab-case | `/api/v1/sales-orders` |
| Permissions | `module.entities.action` | `sales.sales-orders.store` |
| JSON:API fields | camelCase | `createdAt`, `salesOrderId` |
| Database columns | snake_case | `created_at`, `sales_order_id` |

---

## Database Conventions

- Use `float` cast for decimals (not `decimal`)
- JSON fields use `ArrayHash` with associative arrays
- Foreign keys use `onDelete('restrict')`
- Add indexes on filterable/sortable fields

---

## Testing Standards

- Minimum 5 test files per entity: Index, Show, Store, Update, Destroy
- Test all roles: admin, tech, customer, guest
- Use `getAdminUser()`, `getTechUser()`, `getCustomerUser()` helpers
- Use `->jsonApi()->expects()` for JSON:API assertions
- E2E tests in `tests/Integration/` directory

---

## External Integrations

### SW Sapien PAC (CFDI)
- Config: `config/billing.php`
- Keys in `.env`: `SW_PAC_TOKEN`, `CFDI_*`
- Docs: `Modules/Billing/docs/PAC_INTEGRATION.md`

### Stripe
- Gateway: `Modules/Ecommerce/app/Services/Payment/StripePaymentGateway.php`
- Keys in `.env`: `STRIPE_PUBLISHABLE_KEY`, `STRIPE_SECRET_KEY`
- Config: `config/services.php`

---

## Key Services

| Service | Location | Purpose |
|---------|----------|---------|
| CheckoutService | Ecommerce | Cart -> Order flow |
| ARInvoiceService | Finance | Invoice creation with GL posting |
| BudgetControlService | Purchase | PO budget validation |
| DiscountRuleService | Sales | Automatic discount application |
| CycleCountService | Inventory | Inventory count scheduling |
| DuplicateDetectionService | Contacts | Find duplicate contacts |

---

## Event-Driven Architecture

### Key Events
- `SalesOrderCompleted` -> Creates ARInvoice automatically
- `PurchaseOrderReceived` -> Creates APInvoice automatically
- `InventoryMovementCreated` -> Posts to GL

### Listeners Location
- `Modules/Finance/app/Listeners/`
- `Modules/Inventory/app/Listeners/`
