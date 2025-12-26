# Frontend Synchronization Status

**Last Updated:** 2025-12-25
**API Version:** Laravel 12 + JSON:API 5.x
**Total Modules:** 17

## Quick Status

| Module | Docs Status | Priority | Notes |
|--------|-------------|----------|-------|
| Auth | ✅ Complete | - | Login, logout, token management |
| User | ✅ Complete | - | User CRUD, profile |
| PermissionManager | ✅ Complete | - | Roles and permissions |
| Audit | ✅ Complete | - | Activity logs |
| PageBuilder | ✅ Complete | - | CMS pages |
| Product | ✅ Complete | - | Products, Categories, Brands, Units |
| Inventory | ✅ Complete | - | Warehouses, Locations, Stock, Movements |
| Purchase | ✅ Complete | - | Suppliers, Purchase Orders |
| Sales | ✅ Complete | - | Customers, Sales Orders |
| Contacts | ✅ Complete | - | Contacts, Documents, Persons |
| Finance | ✅ Complete | - | AR/AP Invoices, Payments |
| Accounting | ✅ Complete | - | Accounts, Journal Entries, Fiscal Periods |
| Reports | ✅ Complete | - | 10 virtual reports |
| Ecommerce | ✅ Complete | - | 15 entities (carts, checkout, reviews, etc.) |
| HR | ✅ Complete | - | 9 entities (employees, payroll, etc.) |
| CRM | ✅ Complete | - | 4 entities (leads, campaigns, activities) |
| Billing | ✅ Complete | - | CFDI invoicing, PAC integration |

---

## Recent Backend Changes (2025-12-25)

### Ecommerce Module
- **ProductReview**: Fixed product relationship hydration (removed `readOnly()` from BelongsTo)
- **ProductComparison**: Added proper User import for relationships
- **Wishlist**: Fixed factory relationships

### Billing Module
- **CompanySetting**: Added `pacPassword` and `keyPassword` as hidden write-only fields
- **CompanySetting**: Fixed validation for PATCH (required fields now use `sometimes` for updates)
- **PaymentTransaction**: Validated gateway field is required on creation

### HR Module
- **EmployeeIndex/Show**: Fixed field assertions in tests

### CRM Module
- **UpdateCampaign**: Fixed field assertions in tests

---

## Documentation Files Location

All frontend integration guides are located in:
```
docs/modules/
├── ACCOUNTING_FRONTEND_GUIDE.md
├── AUDIT_FRONTEND_GUIDE.md
├── AUTH_FRONTEND_GUIDE.md
├── BILLING_FRONTEND_GUIDE.md
├── CONTACTS_FRONTEND_GUIDE.md
├── CRM_FRONTEND_GUIDE.md
├── ECOMMERCE_FRONTEND_GUIDE.md
├── FINANCE_FRONTEND_GUIDE.md
├── HR_FRONTEND_GUIDE.md
├── INVENTORY_FRONTEND_GUIDE.md
├── PAGEBUILDER_FRONTEND_GUIDE.md
├── PERMISSION_MANAGER_FRONTEND_GUIDE.md
├── PRODUCT_FRONTEND_GUIDE.md
├── PURCHASE_FRONTEND_GUIDE.md
├── REPORTS_FRONTEND_GUIDE.md
├── SALES_FRONTEND_GUIDE.md
└── USER_FRONTEND_GUIDE.md
```

Main integration guide: `docs/FRONTEND_INTEGRATION_GUIDE.md`

---

## Module Priority for Frontend Review

### Priority 1: Core Business (Review First)
1. **Sales** - Customer orders, invoicing integration
2. **Purchase** - Supplier orders, inventory integration
3. **Inventory** - Stock management, movements
4. **Finance** - AR/AP, payments

### Priority 2: Extended Features
5. **Ecommerce** - Shopping cart, checkout, reviews (15 entities)
6. **HR** - Employees, payroll, attendance (9 entities)
7. **CRM** - Leads, campaigns, activities (4 entities)
8. **Billing** - Mexican CFDI invoicing

### Priority 3: Support Modules
9. **Product** - Product catalog
10. **Contacts** - Contact management
11. **Accounting** - GL, journal entries
12. **Reports** - Financial statements

### Priority 4: System Modules
13. **Auth** - Authentication
14. **User** - User management
15. **PermissionManager** - RBAC
16. **Audit** - Activity logging
17. **PageBuilder** - CMS

---

## API Conventions

### JSON:API Format
All endpoints follow JSON:API 1.1 specification:
- Resource type in plural kebab-case: `sales-orders`, `payroll-items`
- Attributes in camelCase: `orderNumber`, `basicSalary`
- Relationships via `included` array

### Authentication
```javascript
const headers = {
  'Authorization': `Bearer ${token}`,
  'Accept': 'application/vnd.api+json',
  'Content-Type': 'application/vnd.api+json'
};
```

### Common Query Parameters
- `filter[field]=value` - Filtering
- `sort=field,-field` - Sorting (prefix `-` for descending)
- `include=relation1,relation2` - Include relationships
- `page[number]=1&page[size]=15` - Pagination

---

## Testing the API

### Default Admin User
```
Email: admin@example.com
Password: secureadmin
```

### Get Token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"secureadmin"}'
```

### Example Request
```bash
curl http://localhost:8000/api/v1/sales-orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/vnd.api+json"
```

---

## Related Documentation

- [Module Implementation Methodology](development/MODULE_IMPLEMENTATION_METHODOLOGY.md)
- [Database Schema Reference](DATABASE_SCHEMA_REFERENCE.md)
- [Business Rules](architecture/BUSINESS_RULES_COMPLETE.md)
- [Development Roadmap](DEVELOPMENT_ROADMAP.md)
