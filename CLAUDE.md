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

**Version:** v1.0-rc1 (2026-01-03)
**Production Readiness:** 99%

### Modules (13/13 Complete)

| Module | Entities | Tests |
|--------|----------|-------|
| Product | Products, Units, Categories, Brands, Variants | 90+ |
| Inventory | Warehouses, Locations, Stock, Batches, Movements | 100+ |
| Purchase | Suppliers, Orders, Items, Approval | 150+ |
| Sales | Customers, Orders, Shipments, Backorders | 200+ |
| Ecommerce | Carts, Checkout, Wishlists, Reviews, Recommendations | 250+ |
| Finance | AP/AR Invoices, Payments, Bank Accounts | 200+ |
| Accounting | Accounts, Journal Entries, Fiscal Periods | 150+ |
| Reports | Financial Statements, Management Reports | 50+ |
| HR | Employees, Attendance, Payroll, Leave, Performance | 400+ |
| CRM | Pipeline, Leads, Campaigns, Activities, Opportunities | 250+ |
| Billing | CFDI, PAC (SW Sapien), Stripe | 60+ |
| Audit | Activity Logging | 20+ |
| SystemHealth | Health Checks | 10+ |

### Metrics
- **Entities:** 65+
- **API Endpoints:** 320+
- **Tests:** 3,300+ (62,000+ assertions)
- **Business Rules:** 165/175 (94%)

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
- **API:** JSON:API 5.x
- **Modules:** nwidart/laravel-modules
- **Auth:** Laravel Sanctum + Spatie Permission
- **Testing:** PHPUnit (SQLite for speed)

---

## Common Commands

```bash
# Run all tests
php artisan test

# Run module tests
php artisan test Modules/{Module}/

# Fresh database
php artisan migrate:fresh --seed

# Create module
php artisan module:advanced-blueprint {Name} --entities="Entity1,Entity2"
```

---

## Test Fix Workflow

When a test fails, check in this order:

```
1. Model      → fillable, casts, relationships
2. Migration  → columns match model
3. Schema     → fields(), filters(), pagination()
4. Request    → validation rules
5. Authorizer → 10 methods, permissions (plural)
6. Factory    → generates valid data
7. Seed       → permissions created
8. Routes     → resource registered
9. Server.php → schema + authorizer uncommented
10. Tests     → data format matches schema
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

---

## External Integrations

### SW Sapien PAC (CFDI)
- Config: `config/billing.php`
- Keys in `.env`: `SW_PAC_TOKEN`, `CFDI_*`
- Docs: `Modules/Billing/docs/PAC_INTEGRATION.md`

### Stripe
- Keys in `.env.testing` (not committed)
- Config: `config/services.php`
