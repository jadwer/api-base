# Audit System Implementation Roadmap

**Created:** 2025-12-28
**Status:** In Progress
**Priority:** High

## Overview

This document tracks the implementation of comprehensive audit logging across all modules. The goal is to track all user actions (creates, updates, deletes) and system events (logins, logouts, critical operations).

## Current State

### Already Implemented
- [x] Spatie Activity Log installed and configured
- [x] `activity_log` table with performance indexes
- [x] `Modules/Audit` module with API structure
- [x] `AuditTrailService` for critical financial operations
- [x] Permissions for audit access (audit.index, audit.show, etc.)

### Models WITH LogsActivity (20 of 74)

**Previously Implemented (6):**
- [x] `User` (Modules/User)
- [x] `Page` (Modules/PageBuilder)
- [x] `InventoryMovement` (Modules/Inventory)
- [x] `PurchaseOrderItem` (Modules/Purchase)
- [x] `SalesOrderItem` (Modules/Sales)

**Tier 1 - Financial (8) - Added 2025-12-28:**
- [x] `SalesOrder` (Modules/Sales)
- [x] `PurchaseOrder` (Modules/Purchase) - REACTIVATED
- [x] `APInvoice` (Modules/Finance)
- [x] `ARInvoice` (Modules/Finance)
- [x] `Payment` (Modules/Finance)
- [x] `CFDIInvoice` (Modules/Billing)
- [x] `JournalEntry` (Modules/Accounting)

**Tier 2 - Master Data (6) - Added 2025-12-28:**
- [x] `Product` (Modules/Product)
- [x] `Contact` (Modules/Contacts)
- [x] `Employee` (Modules/HR)
- [x] `Lead` (Modules/CRM)
- [x] `Opportunity` (Modules/CRM)
- [x] `Campaign` (Modules/CRM)

---

## Implementation Plan

### Phase 1: Enable Infrastructure (COMPLETED)
- [x] Enable Audit API routes in `Modules/Audit/routes/jsonapi.php`
- [ ] Verify Audit tests pass

### Phase 2: Add LogsActivity to Critical Models

Priority order based on business impact:

#### Tier 1 - Financial/Transactional (HIGH PRIORITY)
| Model | Module | Status | Fields to Log |
|-------|--------|--------|---------------|
| SalesOrder | Sales | [ ] | status, total_amount, contact_id, order_date |
| PurchaseOrder | Purchase | [ ] | status, total_amount, contact_id, order_date (reactivate) |
| APInvoice | Finance | [ ] | status, total_amount, contact_id, due_date |
| ARInvoice | Finance | [ ] | status, total_amount, contact_id, due_date |
| Payment | Finance | [ ] | amount, payment_date, status, payment_method_id |
| CFDIInvoice | Billing | [ ] | status, total, uuid, stamped_at, cancelled_at |
| PaymentTransaction | Billing | [ ] | status, amount, gateway_response |
| JournalEntry | Accounting | [ ] | status, total_debit, total_credit, posted_at |

#### Tier 2 - Master Data (MEDIUM PRIORITY)
| Model | Module | Status | Fields to Log |
|-------|--------|--------|---------------|
| Product | Product | [ ] | name, sku, price, cost, is_active |
| Contact | Contacts | [ ] | name, email, phone, is_customer, is_supplier |
| Employee | HR | [ ] | employee_number, status, salary, department_id |
| Lead | CRM | [ ] | title, status, rating, estimated_value |
| Opportunity | CRM | [ ] | name, amount, probability, status, stage |
| Campaign | CRM | [ ] | name, status, budget, actual_cost |

#### Tier 3 - Supporting Entities (LOWER PRIORITY)
| Model | Module | Status | Fields to Log |
|-------|--------|--------|---------------|
| Brand | Product | [ ] | name, is_active |
| Category | Product | [ ] | name, parent_id, is_active |
| Warehouse | Inventory | [ ] | name, code, is_active |
| Department | HR | [ ] | name, code, manager_id |
| Position | HR | [ ] | name, department_id |
| BankAccount | Finance | [ ] | name, account_number, is_active |
| Account | Accounting | [ ] | name, code, account_type |

#### Tier 4 - Operational/Transient (OPTIONAL)
| Model | Module | Status | Notes |
|-------|--------|--------|-------|
| Stock | Inventory | [ ] | High volume, consider sampling |
| Attendance | HR | [ ] | High volume |
| Activity | CRM | [ ] | Already activity tracking |
| CartItem | Ecommerce | [ ] | Transient data |
| CheckoutSession | Ecommerce | [ ] | Transient data |

### Phase 3: Login/Logout Tracking
- [ ] Create middleware or listener for login events
- [ ] Create middleware or listener for logout events
- [ ] Log: user_id, ip_address, user_agent, timestamp
- [ ] Consider failed login attempts tracking

### Phase 4: Testing
- [ ] Run existing Audit module tests
- [ ] Add tests for LogsActivity on key models
- [ ] Test login/logout event logging

### Phase 5: Documentation
- [ ] Update AUDIT_FRONTEND_GUIDE.md with new endpoints
- [ ] Document available filters and sorting
- [ ] Add examples for common queries

---

## Technical Implementation Pattern

### Adding LogsActivity to a Model

```php
<?php

namespace Modules\{Module}\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class {ModelName} extends Model
{
    use LogsActivity;  // Add this trait

    // ... existing code ...

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'field1', 'field2', 'field3',  // Fields to track
            ])
            ->logOnlyDirty()      // Only log changed fields
            ->dontSubmitEmptyLogs(); // Skip if no changes
    }
}
```

### Login Event Listener

```php
// In EventServiceProvider or dedicated listener
Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
    activity()
        ->causedBy($event->user)
        ->withProperties([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ])
        ->log('login');
});

Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
    activity()
        ->causedBy($event->user)
        ->log('logout');
});
```

---

## API Endpoints (Once Enabled)

### List Audits
```
GET /api/v1/audits
```

**Filters:**
- `filter[causer]` - User ID who performed action
- `filter[event]` - Event type (created, updated, deleted, login, logout)
- `filter[auditableType]` - Model class (e.g., "Modules\\Product\\Models\\Product")
- `filter[auditableId]` - Specific model ID

**Sorting:**
- `sort=-createdAt` (newest first)
- `sort=event`
- `sort=auditableType`

**Example:**
```
GET /api/v1/audits?filter[causer]=5&filter[event]=updated&sort=-createdAt
```

### Show Single Audit
```
GET /api/v1/audits/{id}
```

---

## Frontend Integration Notes

### No Breaking Changes
- Adding LogsActivity to models is transparent to existing API consumers
- Audit logs are created automatically on create/update/delete operations
- No changes needed to existing frontend code

### New Features Available
- New `/api/v1/audits` endpoint for viewing activity history
- Can show "Recent Activity" widget on dashboard
- Can show "History" tab on entity detail pages
- Can implement "Who changed this?" functionality

### Suggested UI Components
1. **Activity Timeline** - Show recent actions by current user
2. **Entity History** - Show all changes to a specific record
3. **User Activity Report** - Admin view of all user actions
4. **Login History** - Show login/logout times for security

---

## Progress Tracking

### Completed Steps
1. [x] Audit API routes enabled (2025-12-28)
2. [x] LogsActivity added to Tier 1 models (2025-12-28)
   - SalesOrder, PurchaseOrder, APInvoice, ARInvoice
   - Payment, CFDIInvoice, JournalEntry
3. [x] LogsActivity added to Tier 2 models (2025-12-28)
   - Product, Contact, Employee
   - Lead, Opportunity, Campaign
4. [x] Login/Logout tracking implemented (2025-12-28)
   - Login events with IP, user agent
   - Logout events
   - Failed login attempts

### Current Step
5. [ ] Testing - verify audit tests pass

### Next Steps
6. [ ] Documentation update (AUDIT_FRONTEND_GUIDE.md)

---

## Estimated Effort

| Phase | Models | Time Estimate |
|-------|--------|---------------|
| Phase 2 Tier 1 | 8 models | 30 min |
| Phase 2 Tier 2 | 6 models | 20 min |
| Phase 2 Tier 3 | 7 models | 20 min |
| Phase 3 | Login/Logout | 15 min |
| Phase 4 | Testing | 30 min |
| Phase 5 | Documentation | 20 min |
| **Total** | **21+ models** | **~2.5 hours** |

---

## Notes

- Tests are currently running in background (task bcb4c29)
- Wait for tests to complete before running new test suite
- Consider running audit-specific tests separately to verify implementation
