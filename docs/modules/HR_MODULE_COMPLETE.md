# HR Module - Complete Implementation Reference

## Overview

The HR (Human Resources) module provides comprehensive workforce management capabilities including employee management, attendance tracking, leave management, payroll processing, and performance reviews.

**Implementation Status:** 100% Complete (Phase 4.4)
**Completion Date:** October 31, 2025

## Module Statistics

| Metric | Count |
|--------|-------|
| Entities | 9 |
| PHP Files | 131 |
| Test Files | 45 |
| API Endpoints | 49 |
| Permissions | 45 |
| Test Cases | 400+ |

## Entities

### 1. Department
Organizational structure management.

**Fields:**
- `id` - Primary key
- `name` - Department name (required, max 255)
- `code` - Unique department code (required, max 50)
- `description` - Department description (optional)
- `parentId` - Parent department for hierarchy (optional)
- `managerId` - Department manager (optional, references employees)
- `isActive` - Active status (default: true)
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `parent` - BelongsTo Department (self-referential)
- `children` - HasMany Department
- `manager` - BelongsTo Employee
- `employees` - HasMany Employee
- `positions` - HasMany Position

### 2. Position
Job positions within departments.

**Fields:**
- `id` - Primary key
- `departmentId` - Department reference (required)
- `name` - Position title (required, max 255)
- `code` - Unique position code (required, max 50)
- `description` - Position description (optional)
- `minSalary` - Minimum salary range (optional)
- `maxSalary` - Maximum salary range (optional)
- `isActive` - Active status (default: true)
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `department` - BelongsTo Department
- `employees` - HasMany Employee

### 3. Employee
Core employee records.

**Fields:**
- `id` - Primary key
- `userId` - User account reference (optional)
- `departmentId` - Department assignment (required)
- `positionId` - Position assignment (required)
- `employeeNumber` - Unique employee ID (required, max 50)
- `firstName` - First name (required, max 100)
- `lastName` - Last name (required, max 100)
- `email` - Work email (required, unique)
- `phone` - Phone number (optional)
- `hireDate` - Employment start date (required)
- `terminationDate` - Employment end date (optional)
- `salary` - Current salary (required, numeric)
- `employmentType` - full_time, part_time, contract, intern
- `status` - active, on_leave, suspended, terminated
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `user` - BelongsTo User
- `department` - BelongsTo Department
- `position` - BelongsTo Position
- `attendances` - HasMany Attendance
- `leaves` - HasMany Leave
- `payrollItems` - HasMany PayrollItem
- `performanceReviews` - HasMany PerformanceReview

### 4. Attendance
Daily attendance tracking.

**Fields:**
- `id` - Primary key
- `employeeId` - Employee reference (required)
- `date` - Attendance date (required)
- `checkIn` - Check-in time (optional)
- `checkOut` - Check-out time (optional)
- `hoursWorked` - Auto-calculated hours (computed)
- `overtimeHours` - Overtime hours (computed)
- `status` - present, absent, late, half_day, remote
- `notes` - Additional notes (optional)
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Auto-Calculated Fields:**
- `hoursWorked` - Calculated from checkIn/checkOut difference
- `overtimeHours` - Hours exceeding 8-hour workday

**Relationships:**
- `employee` - BelongsTo Employee

### 5. LeaveType
Leave category definitions.

**Fields:**
- `id` - Primary key
- `name` - Leave type name (required, max 100)
- `code` - Unique code (required, max 20)
- `description` - Type description (optional)
- `daysAllowed` - Annual days allowed (required, integer)
- `isPaid` - Paid leave flag (default: true)
- `requiresApproval` - Approval required flag (default: true)
- `isActive` - Active status (default: true)
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `leaves` - HasMany Leave

### 6. Leave
Employee leave requests.

**Fields:**
- `id` - Primary key
- `employeeId` - Employee reference (required)
- `leaveTypeId` - Leave type reference (required)
- `startDate` - Leave start date (required)
- `endDate` - Leave end date (required)
- `totalDays` - Days requested (computed)
- `reason` - Leave reason (optional)
- `status` - pending, approved, rejected, cancelled
- `approvedById` - Approving user (optional)
- `approvedAt` - Approval timestamp (optional)
- `rejectionReason` - Rejection reason (optional)
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Auto-Calculated Fields:**
- `totalDays` - Calculated from startDate/endDate difference

**Relationships:**
- `employee` - BelongsTo Employee
- `leaveType` - BelongsTo LeaveType
- `approvedBy` - BelongsTo User

### 7. PayrollPeriod
Payroll processing periods.

**Fields:**
- `id` - Primary key
- `name` - Period name (required, max 100)
- `startDate` - Period start (required)
- `endDate` - Period end (required)
- `payDate` - Payment date (required)
- `status` - draft, processing, completed, cancelled
- `totalGross` - Total gross amount (computed)
- `totalDeductions` - Total deductions (computed)
- `totalNet` - Total net amount (computed)
- `notes` - Additional notes (optional)
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Auto-Calculated Fields:**
- `totalGross` - Sum of all payroll items gross
- `totalDeductions` - Sum of all deductions
- `totalNet` - totalGross - totalDeductions

**Relationships:**
- `payrollItems` - HasMany PayrollItem

### 8. PayrollItem
Individual payroll entries per employee.

**Fields:**
- `id` - Primary key
- `payrollPeriodId` - Period reference (required)
- `employeeId` - Employee reference (required)
- `baseSalary` - Base salary amount (required)
- `overtimePay` - Overtime compensation (optional)
- `bonuses` - Bonus amounts (optional)
- `deductions` - Deduction amounts (optional)
- `taxAmount` - Tax withholding (optional)
- `netPay` - Net payment (computed)
- `hoursWorked` - Total hours worked (optional)
- `overtimeHours` - Overtime hours (optional)
- `status` - pending, processed, paid
- `notes` - Additional notes (optional)
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Auto-Calculated Fields:**
- `netPay` - baseSalary + overtimePay + bonuses - deductions - taxAmount

**Relationships:**
- `payrollPeriod` - BelongsTo PayrollPeriod
- `employee` - BelongsTo Employee

### 9. PerformanceReview
Employee performance evaluations.

**Fields:**
- `id` - Primary key
- `employeeId` - Employee reference (required)
- `reviewerId` - Reviewing user (required)
- `reviewPeriodStart` - Review period start (required)
- `reviewPeriodEnd` - Review period end (required)
- `overallRating` - Rating 1-5 (required)
- `strengths` - Identified strengths (optional)
- `areasForImprovement` - Improvement areas (optional)
- `goals` - Future goals (optional)
- `comments` - Reviewer comments (optional)
- `status` - draft, submitted, acknowledged
- `acknowledgedAt` - Employee acknowledgment (optional)
- `metadata` - Additional JSON data
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `employee` - BelongsTo Employee
- `reviewer` - BelongsTo User

## API Endpoints

All endpoints follow JSON:API 1.1 specification.

### Departments
```
GET    /api/v1/departments              - List departments
POST   /api/v1/departments              - Create department
GET    /api/v1/departments/{id}         - Show department
PATCH  /api/v1/departments/{id}         - Update department
DELETE /api/v1/departments/{id}         - Delete department
```

### Positions
```
GET    /api/v1/positions                - List positions
POST   /api/v1/positions                - Create position
GET    /api/v1/positions/{id}           - Show position
PATCH  /api/v1/positions/{id}           - Update position
DELETE /api/v1/positions/{id}           - Delete position
```

### Employees
```
GET    /api/v1/employees                - List employees
POST   /api/v1/employees                - Create employee
GET    /api/v1/employees/{id}           - Show employee
PATCH  /api/v1/employees/{id}           - Update employee
DELETE /api/v1/employees/{id}           - Delete employee
```

### Attendances
```
GET    /api/v1/attendances              - List attendances
POST   /api/v1/attendances              - Create attendance
GET    /api/v1/attendances/{id}         - Show attendance
PATCH  /api/v1/attendances/{id}         - Update attendance
DELETE /api/v1/attendances/{id}         - Delete attendance
```

### Leave Types
```
GET    /api/v1/leave-types              - List leave types
POST   /api/v1/leave-types              - Create leave type
GET    /api/v1/leave-types/{id}         - Show leave type
PATCH  /api/v1/leave-types/{id}         - Update leave type
DELETE /api/v1/leave-types/{id}         - Delete leave type
```

### Leaves
```
GET    /api/v1/leaves                   - List leaves
POST   /api/v1/leaves                   - Create leave
GET    /api/v1/leaves/{id}              - Show leave
PATCH  /api/v1/leaves/{id}              - Update leave
DELETE /api/v1/leaves/{id}              - Delete leave
```

### Payroll Periods
```
GET    /api/v1/payroll-periods          - List payroll periods
POST   /api/v1/payroll-periods          - Create payroll period
GET    /api/v1/payroll-periods/{id}     - Show payroll period
PATCH  /api/v1/payroll-periods/{id}     - Update payroll period
DELETE /api/v1/payroll-periods/{id}     - Delete payroll period
```

### Payroll Items
```
GET    /api/v1/payroll-items            - List payroll items
POST   /api/v1/payroll-items            - Create payroll item
GET    /api/v1/payroll-items/{id}       - Show payroll item
PATCH  /api/v1/payroll-items/{id}       - Update payroll item
DELETE /api/v1/payroll-items/{id}       - Delete payroll item
```

### Performance Reviews
```
GET    /api/v1/performance-reviews      - List performance reviews
POST   /api/v1/performance-reviews      - Create performance review
GET    /api/v1/performance-reviews/{id} - Show performance review
PATCH  /api/v1/performance-reviews/{id} - Update performance review
DELETE /api/v1/performance-reviews/{id} - Delete performance review
```

## Permissions

Each entity has 5 standard permissions:
- `{entity}.index` - List resources
- `{entity}.store` - Create resources
- `{entity}.show` - View single resource
- `{entity}.update` - Update resources
- `{entity}.destroy` - Delete resources

### Permission Matrix

| Role | departments | positions | employees | attendances | leave-types | leaves | payroll-periods | payroll-items | performance-reviews |
|------|-------------|-----------|-----------|-------------|-------------|--------|-----------------|---------------|---------------------|
| god | Full | Full | Full | Full | Full | Full | Full | Full | Full |
| admin | Full | Full | Full | Full | Full | Full | Full | Full | Full |
| tech | Read | Read | Read | Read | Read | Read | Read | Read | Read |
| customer | None | None | None | None | None | None | None | None | None |

## Service Layer

### PayrollService

Located at `Modules/HR/app/Services/PayrollService.php`

**Features:**
- Calculate payroll items for period
- Automatic overtime calculation
- Tax withholding computation
- Integration with Accounting module for GL posting
- Period closing and finalization

**Key Methods:**
```php
public function calculatePayrollForPeriod(PayrollPeriod $period): Collection
public function processPayrollItem(PayrollItem $item): PayrollItem
public function closePayrollPeriod(PayrollPeriod $period): void
public function generatePayslip(PayrollItem $item): array
```

## Business Rules

### Employee Rules
1. Employee number must be unique
2. Email must be unique
3. Hire date cannot be in the future
4. Termination date must be after hire date
5. Salary must be within position's min/max range (if defined)

### Attendance Rules
1. Cannot have duplicate attendance for same employee/date
2. Check-out must be after check-in
3. Hours worked auto-calculated on save
4. Overtime calculated for hours > 8

### Leave Rules
1. Cannot request leave for past dates
2. End date must be after start date
3. Cannot exceed leave type's annual allowance
4. Overlapping leaves not allowed
5. Requires approval if leave type requires it

### Payroll Rules
1. One payroll item per employee per period
2. Period dates cannot overlap
3. Cannot modify closed periods
4. Net pay auto-calculated on save

## Testing

Test files located in `Modules/HR/tests/Feature/`:

- 5 test files per entity (Index, Show, Store, Update, Destroy)
- Total: 45 test files, 400+ test cases
- Coverage: CRUD operations, permissions, validation, relationships

### Running Tests
```bash
# Run all HR tests
php artisan test Modules/HR/tests/Feature/

# Run specific entity tests
php artisan test Modules/HR/tests/Feature/EmployeeIndexTest.php

# Run with filter
php artisan test --filter="Employee"
```

## Database Schema

### Tables Created
1. `departments` - Department records
2. `positions` - Position records
3. `employees` - Employee records
4. `attendances` - Attendance records
5. `leave_types` - Leave type definitions
6. `leaves` - Leave requests
7. `payroll_periods` - Payroll periods
8. `payroll_items` - Payroll items
9. `performance_reviews` - Performance reviews

### Indexes
- All foreign keys indexed
- Unique constraints on codes and employee numbers
- Composite index on attendance(employee_id, date)
- Date range indexes for period queries

## Integration Points

### User Module
- Employees can be linked to User accounts
- Reviewers reference User records

### Accounting Module
- PayrollService integrates with JournalEntry for GL posting
- Payroll expenses posted to configured accounts

## Frontend Integration

See `docs/modules/HR_FRONTEND_GUIDE.md` for complete frontend integration documentation including:
- API request/response examples
- Field validations
- Filter and sort options
- Relationship includes
- Error handling

## Changelog

### Phase 4.4 (October 31, 2025)
- Initial implementation of all 9 entities
- Complete CRUD operations
- Full test coverage
- PayrollService with accounting integration
- Auto-calculated fields for hours and pay
