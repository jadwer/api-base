# 🎨 Diagrams Update Required - Phase 4.4

**Date:** 2025-10-31
**Reason:** HR Module (Phase 4.4), Reports Module (Phase 4.2), Ecommerce Extended (Phase 4.1+4.3) not reflected in visual diagrams
**Action Required:** Manual update in diagrams.net

---

## ⚠️ CRITICAL: Visual Diagrams Are Outdated

The markdown documentation has been updated to reflect **9 modules** and **54+ tables**, but the visual .drawio diagrams still show the old **7 modules** and **39 tables** architecture.

**Current Reality:**
- ✅ Markdown docs: Updated (DATABASE_SCHEMA_REFERENCE.md, ERD_DOCUMENTATION.md, architecture/README.md)
- ❌ Visual diagrams: Outdated (all 13 .drawio files need updates)

---

## 📊 DIAGRAMS REQUIRING UPDATES

### 1. ERD Diagrams (3 files)

#### **ERD-complete-system.drawio** - PRIORITY 1 🔴
**Location:** `docs/architecture/erd/ERD-complete-system.drawio`

**Changes Needed:**
- Add **HR Module section** with 9 tables:
  1. departments (id, name, code, parent_id, manager_id, is_active)
  2. positions (id, title, code, department_id, level, min_salary, max_salary)
  3. employees (id, employee_number, first_name, last_name, email, department_id, position_id, manager_id, salary, employment_type, status)
  4. attendances (id, employee_id, date, check_in, check_out, hours_worked ⚡, overtime_hours ⚡, status)
  5. leave_types (id, name, code, days_per_year, is_paid, requires_approval)
  6. leaves (id, employee_id, leave_type_id, start_date, end_date, days ⚡, status, approved_by)
  7. payroll_periods (id, name, start_date, end_date, payment_date, status, total_gross ⚡, total_deductions ⚡, total_net ⚡)
  8. payroll_items (id, payroll_period_id, employee_id, basic_salary, allowances, deductions, gross_amount ⚡, deductions_total ⚡, net_amount ⚡, status)
  9. performance_reviews (id, employee_id, reviewer_id, review_period_start, review_period_end, review_date, overall_rating, status)

**Relationships to Add:**
- departments.parent_id → departments.id (self-referential)
- departments.manager_id → employees.id
- positions.department_id → departments.id
- employees.department_id → departments.id
- employees.position_id → positions.id
- employees.manager_id → employees.id (self-referential)
- attendances.employee_id → employees.id
- leaves.employee_id → employees.id
- leaves.leave_type_id → leave_types.id
- leaves.approved_by → employees.id
- payroll_periods → payroll_items (hasMany)
- payroll_items.payroll_period_id → payroll_periods.id
- payroll_items.employee_id → employees.id
- performance_reviews.employee_id → employees.id
- performance_reviews.reviewer_id → employees.id

**Visual Notes:**
- ⚡ Mark auto-calculated fields with special icon/color
- Use color coding: HR module in distinct color (suggest: purple or orange)
- Group all 9 HR tables together visually

**Expand Ecommerce Module** (currently shows only 3 tables):
- Add: wishlists, wishlist_items, product_reviews, product_recommendations, currencies, currency_conversions, checkout_sessions, payment_methods, shipping_methods, order_tracking_events
- Total: 11 tables for Ecommerce

**Update Statistics Box:**
- Modules: 7 → 9
- Tables: 39 → 54+
- Relationships: 40+ → 60+

---

#### **ERD-finance-accounting.drawio** - PRIORITY 2 🟡
**Location:** `docs/architecture/erd/ERD-finance-accounting.drawio`

**Changes Needed:**
- Add integration arrow/note: "PayrollService posts to journal_entries"
- Add visual note: "HR.payroll_items → Accounting.journal_entries (automated GL posting)"
- Update diagram title from "17 tables" to "17 tables + HR integration"

---

#### **ERD-sales-purchase-inventory.drawio** - PRIORITY 3 🟢
**Location:** `docs/architecture/erd/ERD-sales-purchase-inventory.drawio`

**Changes Needed:**
- Expand Ecommerce section to show Phase 4.1 + 4.3 tables
- Add: checkout_sessions, payment_methods, shipping_methods, order_tracking_events, wishlists, wishlist_items, product_reviews, product_recommendations
- Update title: "14 tables" → "25+ tables (includes expanded Ecommerce)"

---

### 2. C4 Architecture Diagrams (5 files)

#### **L1-system-context.drawio** - PRIORITY 3 🟢
**Location:** `docs/architecture/c4/L1-system-context.drawio`

**Changes Needed:**
- Add "HR Manager" actor (optional, or expand Admin to cover HR functions)
- Update ERP system box description to mention "9 modules" instead of "7 modules"

---

#### **L2-container.drawio** - PRIORITY 1 🔴
**Location:** `docs/architecture/c4/L2-container.drawio`

**Changes Needed:**
- Add **HR Module container** with:
  - **Title:** HR Module
  - **Entities:** 9 (departments, positions, employees, attendances, leave_types, leaves, payroll_periods, payroll_items, performance_reviews)
  - **Services:** PayrollService
  - **Description:** "Employee management, attendance tracking, leave management, payroll processing, performance reviews"
  - **Integration:** Arrow to Accounting Module: "PayrollService → JournalEntry (GL posting)"

- Update **Ecommerce Module container**:
  - **Entities:** 3 → 11
  - **Description:** Add "Wishlist management, product reviews, recommendations, multi-currency support"

- Add **Reports Module container**:
  - **Title:** Reports Module
  - **Entities:** 0 (service layer only)
  - **Services:** FinancialStatementsService, ManagementReportsService, AnalyticsService
  - **Description:** "Financial statements, management reports, analytics dashboards (reads from existing tables)"

**Update Statistics:**
- Total Modules: 7 → 9
- Total Entities: Update count to 54+

---

#### **L3-component-finance.drawio** - PRIORITY 3 🟢
**Location:** `docs/architecture/c4/L3-component-finance.drawio`

**Changes Needed:**
- No major changes needed (HR doesn't directly integrate with Finance, only via Accounting)

---

#### **L3-component-accounting.drawio** - PRIORITY 2 🟡
**Location:** `docs/architecture/c4/L3-component-accounting.drawio`

**Changes Needed:**
- Add **integration note/box**: "HR PayrollService Integration"
- Show method: `PayrollService::postToGeneralLedger(period, accountMapping)`
- Arrow from HR to AccountingService: "Creates journal entries for payroll"
- Add to "External Callers" section: "HR Module (PayrollService)"

---

#### **L3-component-integration.drawio** - PRIORITY 3 🟢
**Location:** `docs/architecture/c4/L3-component-integration.drawio`

**Changes Needed:**
- Add **HR-to-Accounting integration flow**:
  - Event: PayrollPeriodClosed (optional, or direct service call)
  - Listener/Service: PayrollService.postToGeneralLedger()
  - Result: JournalEntry created in Accounting
- Update total events count if using event-driven approach

---

### 3. Business Flow Diagrams (4 files)

#### **FLOW-order-to-cash.drawio** - PRIORITY 3 🟢
**Location:** `docs/architecture/flows/FLOW-order-to-cash.drawio`

**Changes Needed:**
- No changes needed (HR doesn't affect Order-to-Cash)

---

#### **FLOW-procure-to-pay.drawio** - PRIORITY 3 🟢
**Location:** `docs/architecture/flows/FLOW-procure-to-pay.drawio`

**Changes Needed:**
- No changes needed (HR doesn't affect Procure-to-Pay)

---

#### **FLOW-inventory-management.drawio** - PRIORITY 3 🟢
**Location:** `docs/architecture/flows/FLOW-inventory-management.drawio`

**Changes Needed:**
- No changes needed

---

#### **FLOW-ecommerce-checkout.drawio** - PRIORITY 2 🟡
**Location:** `docs/architecture/flows/FLOW-ecommerce-checkout.drawio`

**Changes Needed:**
- Update to reflect Phase 4.1 enhancements:
  - Add step: "Create Checkout Session"
  - Add step: "Select Shipping Method"
  - Add step: "Select Payment Method"
  - Add step: "Create Order Tracking Event"
- Consider adding "Add to Wishlist" alternative path

---

### 4. Lifecycle Diagrams (1 file)

#### **LIFECYCLE-state-machines.drawio** - PRIORITY 2 🟡
**Location:** `docs/architecture/lifecycle/LIFECYCLE-state-machines.drawio`

**Changes Needed:**
- Add **HR entity state machines**:

  **Leave (approval workflow):**
  - pending → approved → taken
  - pending → rejected
  - approved → cancelled

  **PayrollPeriod:**
  - open → processing → paid → closed
  - closed → reopened (emergency)

  **PayrollItem:**
  - draft → pending → approved → paid

  **PerformanceReview:**
  - draft → submitted → acknowledged → completed

- Update diagram title to include HR lifecycles

---

## 📋 RECOMMENDED DIAGRAM CREATION

### **NEW: ERD-hr-module.drawio**
Create dedicated HR module ERD showing:
- All 9 HR tables with complete field definitions
- All internal HR relationships
- External integrations (HR → Accounting)
- Auto-calculated fields highlighted
- Service layer (PayrollService) interaction

**Location:** `docs/architecture/erd/ERD-hr-module.drawio`

---

### **NEW: FLOW-payroll-processing.drawio**
Create payroll processing flow showing:
1. Open Payroll Period
2. Process Payroll (create PayrollItems for all employees)
3. Review & Approve PayrollItems
4. Mark Period as Paid
5. Post to General Ledger (PayrollService)
6. Close Period

**Swimlanes:** HR Manager, Finance, Accounting, System

**Location:** `docs/architecture/flows/FLOW-payroll-processing.drawio`

---

### **NEW: L3-component-hr.drawio**
Create HR component-level C4 diagram showing:
- PayrollService detailed architecture
- Integration with Accounting module
- Employee hierarchy management
- Attendance calculation logic
- Leave approval workflow

**Location:** `docs/architecture/c4/L3-component-hr.drawio`

---

## 🛠️ HOW TO UPDATE DIAGRAMS

### Step 1: Open in diagrams.net
1. Go to https://app.diagrams.net
2. Open the .drawio file from local filesystem
3. Click "File" → "Open from" → "Device"

### Step 2: Make Updates
- Use copy/paste to duplicate existing module sections for consistency
- Maintain color coding (suggest HR = purple/orange)
- Use crow's foot notation for ERD relationships
- Mark auto-calculated fields with ⚡ or distinct color
- Update legend/statistics boxes

### Step 3: Save
- Click "File" → "Save"
- Overwrite the existing .drawio file
- Commit to git with message: "docs: update diagrams with HR, Reports, Ecommerce modules"

---

## 🎯 PRIORITY ORDER

**High Priority (Do First):**
1. 🔴 ERD-complete-system.drawio - Most visible, core reference
2. 🔴 L2-container.drawio - Architecture overview

**Medium Priority:**
3. 🟡 L3-component-accounting.drawio - Shows HR integration
4. 🟡 ERD-finance-accounting.drawio - Integration note
5. 🟡 LIFECYCLE-state-machines.drawio - HR lifecycles
6. 🟡 FLOW-ecommerce-checkout.drawio - Phase 4.1 updates

**Low Priority (Nice to Have):**
7. 🟢 ERD-sales-purchase-inventory.drawio - Ecommerce expansion
8. 🟢 L1-system-context.drawio - Minor update
9. 🟢 L3-component-integration.drawio - Optional HR event

**Create New (Recommended):**
- ERD-hr-module.drawio
- FLOW-payroll-processing.drawio
- L3-component-hr.drawio

---

## ✅ CHECKLIST

After updating each diagram:
- [ ] ERD-complete-system.drawio - HR Module added, stats updated
- [ ] L2-container.drawio - HR, Reports, Ecommerce updated
- [ ] L3-component-accounting.drawio - HR integration shown
- [ ] ERD-finance-accounting.drawio - Integration note added
- [ ] LIFECYCLE-state-machines.drawio - HR lifecycles added
- [ ] FLOW-ecommerce-checkout.drawio - Phase 4.1 steps added
- [ ] Create ERD-hr-module.drawio (optional)
- [ ] Create FLOW-payroll-processing.drawio (optional)
- [ ] Create L3-component-hr.drawio (optional)

---

## 📝 NOTES

**Why Not Automated?**
- .drawio files are complex XML with visual positioning
- Automated updates could break layout/connections
- Manual editing in diagrams.net ensures visual quality

**Alternative: Text-Based Diagrams**
Consider creating Mermaid diagrams in markdown for auto-generated visuals:
- Can be embedded in markdown files
- Version-controlled as text
- Auto-rendered in GitHub/GitLab

---

**Status:** ⚠️ DIAGRAMS OUTDATED - Manual update required
**Last Updated:** 2025-10-31
**Updated By:** Documentation audit (automated)
