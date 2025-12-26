# Project Documentation

Documentation for the Laravel Modular ERP API system.

---

## Quick Start

| I want to... | Read this |
|-------------|-----------|
| **Integrate with the API (Frontend)** | [Frontend Integration Guide](FRONTEND_INTEGRATION_GUIDE.md) |
| **See current API status** | [Frontend Sync Status](FRONTEND_SYNC_STATUS.md) |
| **Understand the system architecture** | [Architecture Documentation](architecture/README.md) |
| **Get started quickly** | [How to Use Guide](HOW-TO-USE.md) |
| **Create a new module** | [Module Blueprint Master](development/module-blueprint-master.md) |
| **See database schema** | [Database Schema Reference](DATABASE_SCHEMA_REFERENCE.md) |

---

## Documentation Structure

```
docs/
├── README.md                           # This file - Documentation index
├── FRONTEND_INTEGRATION_GUIDE.md       # API integration guide
├── FRONTEND_SYNC_STATUS.md             # Current sync status & recent changes
├── DATABASE_SCHEMA_REFERENCE.md        # Database schema reference
├── DEVELOPMENT_ROADMAP.md              # Active development roadmap
├── HOW-TO-USE.md                       # Getting started guide
├── ADVANCED-BLUEPRINT-GUIDE.md         # Module generator usage
│
├── modules/                            # Frontend Integration Guides (17 modules)
│   ├── ACCOUNTING_FRONTEND_GUIDE.md
│   ├── AUDIT_FRONTEND_GUIDE.md
│   ├── AUTH_FRONTEND_GUIDE.md
│   ├── BILLING_FRONTEND_GUIDE.md
│   ├── CONTACTS_FRONTEND_GUIDE.md
│   ├── CRM_FRONTEND_GUIDE.md
│   ├── ECOMMERCE_FRONTEND_GUIDE.md
│   ├── FINANCE_FRONTEND_GUIDE.md
│   ├── HR_FRONTEND_GUIDE.md
│   ├── INVENTORY_FRONTEND_GUIDE.md
│   ├── PAGEBUILDER_FRONTEND_GUIDE.md
│   ├── PERMISSION_MANAGER_FRONTEND_GUIDE.md
│   ├── PRODUCT_FRONTEND_GUIDE.md
│   ├── PURCHASE_FRONTEND_GUIDE.md
│   ├── REPORTS_FRONTEND_GUIDE.md
│   ├── SALES_FRONTEND_GUIDE.md
│   └── USER_FRONTEND_GUIDE.md
│
├── architecture/                       # System Architecture
│   ├── README.md                       # Architecture index
│   ├── C4_DIAGRAMS_GUIDE.md            # C4 model documentation
│   ├── ERD_DOCUMENTATION.md            # Database schema with diagrams
│   ├── BUSINESS_FLOWS.md               # Order-to-Cash, Procure-to-Pay flows
│   ├── LIFECYCLE_DOCUMENTATION.md      # Entity state machines
│   ├── BUSINESS_RULES_COMPLETE.md      # Business rules inventory
│   ├── c4/                             # DrawIO C4 diagrams
│   ├── erd/                            # DrawIO ERD diagrams
│   ├── flows/                          # DrawIO flow diagrams
│   └── lifecycle/                      # DrawIO lifecycle diagrams
│
├── development/                        # Developer Tools
│   ├── MODULE_IMPLEMENTATION_METHODOLOGY.md
│   └── module-blueprint-master.md
│
└── performance/                        # Performance Documentation
    ├── DATABASE_INDEX_RECOMMENDATIONS.md
    └── OPTIMIZATION_SUMMARY.md
```

---

## For Frontend Developers

### Primary Resources

1. **[Frontend Integration Guide](FRONTEND_INTEGRATION_GUIDE.md)** - Complete API reference
2. **[Frontend Sync Status](FRONTEND_SYNC_STATUS.md)** - Current status and recent changes
3. **[modules/](modules/)** - 17 module-specific integration guides

### Key Endpoints

```javascript
// Authentication
POST /api/auth/login

// Core Modules
GET/POST/PATCH /api/v1/products
GET/POST/PATCH /api/v1/sales-orders
GET/POST/PATCH /api/v1/purchase-orders
GET/POST/PATCH /api/v1/inventory-movements

// Finance & Accounting
GET/POST/PATCH /api/v1/ar-invoices
GET/POST/PATCH /api/v1/ap-invoices
GET/POST/PATCH /api/v1/journal-entries

// Reports (Read-only)
GET /api/v1/reports/balance-sheets
GET /api/v1/reports/income-statements
GET /api/v1/reports/ar-aging-reports
```

---

## For Backend Developers

### Creating a New Module

1. Read: [MODULE_IMPLEMENTATION_METHODOLOGY.md](development/MODULE_IMPLEMENTATION_METHODOLOGY.md)
2. Use: `php artisan module:advanced-blueprint ModuleName --config="config.json"`
3. Reference: [module-blueprint-master.md](development/module-blueprint-master.md)

### Understanding the System

1. **Architecture Overview**: [architecture/README.md](architecture/README.md)
2. **Database Schema**: [ERD_DOCUMENTATION.md](architecture/ERD_DOCUMENTATION.md)
3. **Business Processes**: [BUSINESS_FLOWS.md](architecture/BUSINESS_FLOWS.md)
4. **Business Rules**: [BUSINESS_RULES_COMPLETE.md](architecture/BUSINESS_RULES_COMPLETE.md)

### Quick Commands

```bash
# Run all tests
php artisan test

# Run tests for specific module
php artisan test Modules/Sales/

# Create new module
php artisan module:advanced-blueprint MyModule --entities="Entity1,Entity2"

# Fresh database with seeds
php artisan migrate:fresh --seed
```

---

## Current System Status

| Category | Count |
|----------|-------|
| **Modules** | 17 |
| **Database Tables** | 54+ |
| **API Endpoints** | 200+ |
| **Test Coverage** | 3000+ tests |

### Modules

| Module | Status | Entities |
|--------|--------|----------|
| Product | Complete | Products, Categories, Brands, Units |
| Inventory | Complete | Warehouses, Locations, Stock, Movements, Batches |
| Sales | Complete | Customers, Sales Orders, Order Items |
| Purchase | Complete | Suppliers, Purchase Orders, Order Items |
| Contacts | Complete | Contacts, Addresses, People, Documents |
| Finance | Complete | AR/AP Invoices, Payments, Bank Accounts |
| Accounting | Complete | Accounts, Journal Entries, Fiscal Periods |
| Reports | Complete | 10 virtual reports |
| Ecommerce | Complete | 15 entities (carts, checkout, reviews, etc.) |
| HR | Complete | 9 entities (employees, payroll, etc.) |
| CRM | Complete | 4 entities (leads, campaigns, activities) |
| Billing | Complete | CFDI invoicing, PAC integration |
| Auth | Complete | Authentication |
| User | Complete | User management |
| PermissionManager | Complete | Roles and permissions |
| Audit | Complete | Activity logging |
| PageBuilder | Complete | CMS pages |

---

## External Resources

- **Laravel 12**: https://laravel.com/docs/12.x
- **JSON:API 1.1**: https://jsonapi.org/format/
- **Laravel Sanctum**: https://laravel.com/docs/12.x/sanctum
- **Spatie Permission**: https://spatie.be/docs/laravel-permission

---

**Last Updated**: 2025-12-26
