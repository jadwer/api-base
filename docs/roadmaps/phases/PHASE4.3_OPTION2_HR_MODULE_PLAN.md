# Phase 4.3 Option 2: HR Module - Implementation Plan

**Status:** 📋 Planning
**Start Date:** TBD
**Estimated Duration:** 4-5 days
**Complexity:** Medium (3/5)
**Priority:** 🥉 THIRD (Medium-High Priority)
**Dependencies:** Accounting Module ✅ Complete (for payroll GL posting)

---

## Objective

Implement a comprehensive Human Resources module for employee management, payroll automation with accounting integration, attendance tracking, leave management, and performance reviews. Automate payroll calculation and GL posting to streamline HR operations.

**Business Value:**
- Centralized employee information management
- Automated payroll calculation and GL posting
- Time & attendance tracking for accurate payroll
- Leave management and approval workflows
- Performance review tracking
- Compliance with labor laws

---

## Architecture Decision

**Module Approach:** Create dedicated `HR` module

**Why?**
- Dedicated HR workflows and permissions
- Payroll integration with Accounting module via GL posting
- Sensitive employee data isolation
- Scalable for future benefits administration
- Compliance and audit trail

---

## Implementation Plan

### Stage 1: Employee Management (Day 1, 6-7 hours)

#### 1.1 Database Migrations

**Table: `hr_departments`**
```sql
CREATE TABLE hr_departments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    manager_id BIGINT UNSIGNED,
    parent_department_id BIGINT UNSIGNED,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (manager_id) REFERENCES hr_employees(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_department_id) REFERENCES hr_departments(id) ON DELETE SET NULL,
    INDEX idx_departments_code (code),
    INDEX idx_departments_manager (manager_id)
);
```

**Table: `hr_positions`**
```sql
CREATE TABLE hr_positions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    department_id BIGINT UNSIGNED,
    level VARCHAR(50), -- entry, mid, senior, manager, director
    min_salary DECIMAL(10,2),
    max_salary DECIMAL(10,2),
    description TEXT,
    requirements TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (department_id) REFERENCES hr_departments(id) ON DELETE SET NULL,
    INDEX idx_positions_code (code),
    INDEX idx_positions_department (department_id)
);
```

**Table: `hr_employees`**
```sql
CREATE TABLE hr_employees (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_number VARCHAR(50) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED, -- Link to users table for login
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    date_of_birth DATE,
    gender VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Mexico',

    -- Employment Details
    department_id BIGINT UNSIGNED,
    position_id BIGINT UNSIGNED,
    manager_id BIGINT UNSIGNED,
    hire_date DATE NOT NULL,
    termination_date DATE,
    employment_status VARCHAR(50) DEFAULT 'active', -- active, on_leave, terminated
    employment_type VARCHAR(50) DEFAULT 'full_time', -- full_time, part_time, contract

    -- Compensation
    salary DECIMAL(10,2) NOT NULL,
    salary_currency VARCHAR(3) DEFAULT 'MXN',
    payment_frequency VARCHAR(50) DEFAULT 'monthly', -- weekly, biweekly, monthly
    bank_account_number VARCHAR(100),
    bank_name VARCHAR(100),

    -- Tax & Legal (Mexico)
    rfc VARCHAR(13), -- Registro Federal de Contribuyentes
    curp VARCHAR(18), -- Clave Única de Registro de Población
    nss VARCHAR(11), -- Número de Seguridad Social (IMSS)

    -- Additional
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(50),
    notes TEXT,
    metadata JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES hr_departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES hr_positions(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES hr_employees(id) ON DELETE SET NULL,
    INDEX idx_employees_number (employee_number),
    INDEX idx_employees_user (user_id),
    INDEX idx_employees_department (department_id),
    INDEX idx_employees_status (employment_status),
    INDEX idx_employees_rfc (rfc)
);
```

#### 1.2 Models

**Employee Model:**
```php
class Employee extends Model
{
    protected $table = 'hr_employees';

    protected $fillable = [
        'employee_number', 'user_id', 'first_name', 'last_name', 'email',
        'phone', 'date_of_birth', 'gender', 'address', 'city', 'state',
        'postal_code', 'country', 'department_id', 'position_id', 'manager_id',
        'hire_date', 'termination_date', 'employment_status', 'employment_type',
        'salary', 'salary_currency', 'payment_frequency', 'bank_account_number',
        'bank_name', 'rfc', 'curp', 'nss', 'emergency_contact_name',
        'emergency_contact_phone', 'notes', 'metadata'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'salary' => 'float',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo;
    public function department(): BelongsTo;
    public function position(): BelongsTo;
    public function manager(): BelongsTo;
    public function subordinates(): HasMany; // Employees
    public function attendances(): HasMany;
    public function leaves(): HasMany;
    public function payrolls(): HasMany;

    // Get full name
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

**Department Model:**
```php
class Department extends Model
{
    protected $table = 'hr_departments';

    protected $fillable = ['name', 'code', 'manager_id', 'parent_department_id', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function manager(): BelongsTo; // Employee
    public function parent(): BelongsTo; // Department
    public function children(): HasMany; // Departments
    public function employees(): HasMany;
    public function positions(): HasMany;
}
```

**Position Model:**
```php
class Position extends Model
{
    protected $table = 'hr_positions';

    protected $fillable = [
        'title', 'code', 'department_id', 'level',
        'min_salary', 'max_salary', 'description', 'requirements', 'is_active'
    ];

    protected $casts = [
        'min_salary' => 'float',
        'max_salary' => 'float',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo;
    public function employees(): HasMany;
}
```

#### 1.3 API Endpoints

```
GET    /api/v1/hr/employees                 List employees
POST   /api/v1/hr/employees                 Create employee
GET    /api/v1/hr/employees/{id}            Show employee
PATCH  /api/v1/hr/employees/{id}            Update employee
DELETE /api/v1/hr/employees/{id}            Deactivate employee
GET    /api/v1/hr/departments               List departments
GET    /api/v1/hr/positions                 List positions
```

#### 1.4 Testing

Create 8 test files (5 for Employee, 2 for Department, 1 for Position)

**Test Scenarios:**
- Create/update employee with full details
- Link employee to user account
- Hierarchical department structure
- Manager-subordinate relationships
- Filter by department/status
- Employee termination workflow

---

### Stage 2: Attendance & Time Tracking (Day 2, 5-6 hours)

#### 2.1 Database Migration

**Table: `hr_attendances`**
```sql
CREATE TABLE hr_attendances (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    check_in_time TIME,
    check_out_time TIME,
    hours_worked DECIMAL(5,2), -- Calculated
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'present', -- present, absent, late, half_day
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (employee_id) REFERENCES hr_employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_date (employee_id, date),
    INDEX idx_attendances_employee (employee_id),
    INDEX idx_attendances_date (date),
    INDEX idx_attendances_status (status)
);
```

**Table: `hr_leave_types`**
```sql
CREATE TABLE hr_leave_types (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    annual_quota INTEGER DEFAULT 0, -- Days per year
    requires_approval BOOLEAN DEFAULT TRUE,
    is_paid BOOLEAN DEFAULT TRUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_leave_types_code (code)
);
```

**Table: `hr_leaves`**
```sql
CREATE TABLE hr_leaves (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INTEGER NOT NULL,
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending', -- pending, approved, rejected, cancelled
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (employee_id) REFERENCES hr_employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES hr_leave_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_leaves_employee (employee_id),
    INDEX idx_leaves_status (status),
    INDEX idx_leaves_dates (start_date, end_date)
);
```

#### 2.2 Models & Services

**Attendance Model:**
```php
class Attendance extends Model
{
    protected $table = 'hr_attendances';

    protected $fillable = [
        'employee_id', 'date', 'check_in_time', 'check_out_time',
        'hours_worked', 'overtime_hours', 'status', 'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'hours_worked' => 'float',
        'overtime_hours' => 'float',
    ];

    public function employee(): BelongsTo;

    // Auto-calculate hours worked
    protected static function booted()
    {
        static::saving(function ($attendance) {
            if ($attendance->check_in_time && $attendance->check_out_time) {
                $checkIn = Carbon::parse($attendance->check_in_time);
                $checkOut = Carbon::parse($attendance->check_out_time);
                $attendance->hours_worked = $checkOut->diffInHours($checkIn, true);
            }
        });
    }
}
```

**Leave Model:**
```php
class Leave extends Model
{
    protected $table = 'hr_leaves';

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date',
        'total_days', 'reason', 'status', 'approved_by', 'approved_at', 'rejection_reason'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo;
    public function leaveType(): BelongsTo;
    public function approvedBy(): BelongsTo; // User

    // Calculate business days
    protected static function booted()
    {
        static::saving(function ($leave) {
            if ($leave->start_date && $leave->end_date) {
                $leave->total_days = $leave->start_date->diffInWeekdays($leave->end_date) + 1;
            }
        });
    }
}
```

**AttendanceService:**
```php
class AttendanceService
{
    public function checkIn(Employee $employee, Carbon $dateTime): Attendance;
    public function checkOut(Employee $employee, Carbon $dateTime): Attendance;
    public function getMonthlyReport(Employee $employee, int $month, int $year): array;
    public function calculateOvertime(Employee $employee, Carbon $date): float;
}
```

**LeaveService:**
```php
class LeaveService
{
    public function requestLeave(Employee $employee, array $data): Leave;
    public function approveLeave(Leave $leave, User $approver): void;
    public function rejectLeave(Leave $leave, User $approver, string $reason): void;
    public function getLeaveBalance(Employee $employee, LeaveType $type): int;
}
```

#### 2.3 API Endpoints

```
POST   /api/v1/hr/attendances/check-in      Check in
POST   /api/v1/hr/attendances/check-out     Check out
GET    /api/v1/hr/attendances/report        Attendance report
GET    /api/v1/hr/leaves                    List leaves
POST   /api/v1/hr/leaves                    Request leave
PATCH  /api/v1/hr/leaves/{id}/approve       Approve leave
PATCH  /api/v1/hr/leaves/{id}/reject        Reject leave
GET    /api/v1/hr/leaves/balance            Leave balance
```

#### 2.4 Testing

Create 6 test files

**Test Scenarios:**
- Check-in/check-out flow
- Automatic hours calculation
- Overtime detection
- Leave request workflow
- Leave approval/rejection
- Leave balance calculation

---

### Stage 3: Payroll Management (Day 3-4, 10-12 hours)

#### 3.1 Database Migration

**Table: `hr_payroll_periods`**
```sql
CREATE TABLE hr_payroll_periods (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    period_type VARCHAR(50) NOT NULL, -- weekly, biweekly, monthly
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    pay_date DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'draft', -- draft, calculated, approved, paid, posted
    total_gross DECIMAL(10,2) DEFAULT 0,
    total_deductions DECIMAL(10,2) DEFAULT 0,
    total_net DECIMAL(10,2) DEFAULT 0,
    journal_entry_id BIGINT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE SET NULL,
    INDEX idx_payroll_periods_dates (start_date, end_date),
    INDEX idx_payroll_periods_status (status)
);
```

**Table: `hr_payroll_items`**
```sql
CREATE TABLE hr_payroll_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payroll_period_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,

    -- Earnings
    base_salary DECIMAL(10,2) NOT NULL,
    overtime_pay DECIMAL(10,2) DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0,
    commission DECIMAL(10,2) DEFAULT 0,
    other_earnings DECIMAL(10,2) DEFAULT 0,
    total_gross DECIMAL(10,2) NOT NULL,

    -- Deductions
    income_tax DECIMAL(10,2) DEFAULT 0, -- ISR (Mexico)
    social_security DECIMAL(10,2) DEFAULT 0, -- IMSS
    other_deductions DECIMAL(10,2) DEFAULT 0,
    total_deductions DECIMAL(10,2) DEFAULT 0,

    -- Net Pay
    net_pay DECIMAL(10,2) NOT NULL,

    -- Attendance Data (for calculation)
    days_worked INTEGER DEFAULT 0,
    hours_worked DECIMAL(5,2) DEFAULT 0,
    overtime_hours DECIMAL(5,2) DEFAULT 0,

    status VARCHAR(50) DEFAULT 'draft', -- draft, approved, paid
    paid_at TIMESTAMP NULL,
    notes TEXT,
    metadata JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (payroll_period_id) REFERENCES hr_payroll_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES hr_employees(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_period_employee (payroll_period_id, employee_id),
    INDEX idx_payroll_items_period (payroll_period_id),
    INDEX idx_payroll_items_employee (employee_id)
);
```

#### 3.2 Payroll Service

**PayrollService:**
```php
class PayrollService
{
    /**
     * Create payroll period and calculate for all active employees
     */
    public function createPayrollPeriod(Carbon $startDate, Carbon $endDate, Carbon $payDate): PayrollPeriod;

    /**
     * Calculate payroll for a single employee
     */
    public function calculateEmployeePayroll(PayrollPeriod $period, Employee $employee): PayrollItem;

    /**
     * Calculate gross pay (base salary + overtime + bonuses)
     */
    protected function calculateGrossPay(Employee $employee, PayrollPeriod $period): array;

    /**
     * Calculate deductions (taxes, social security)
     */
    protected function calculateDeductions(float $grossPay, Employee $employee): array;

    /**
     * Calculate Mexican income tax (ISR)
     */
    protected function calculateISR(float $grossPay): float;

    /**
     * Calculate Mexican social security (IMSS)
     */
    protected function calculateIMSS(float $grossPay): float;

    /**
     * Approve payroll period
     */
    public function approvePayrollPeriod(PayrollPeriod $period): void;

    /**
     * Post payroll to accounting (create Journal Entry)
     */
    public function postToAccounting(PayrollPeriod $period): JournalEntry;

    /**
     * Mark payroll as paid
     */
    public function markAsPaid(PayrollPeriod $period): void;
}
```

**Accounting Integration (GL Posting):**
```php
public function postToAccounting(PayrollPeriod $period): JournalEntry
{
    $journalEntry = JournalEntry::create([
        'entry_date' => $period->pay_date,
        'description' => "Payroll Period: {$period->name}",
        'type' => 'standard',
        'fiscal_period_id' => FiscalPeriod::getCurrentPeriod()->id,
        'status' => 'draft',
    ]);

    // Debit: Payroll Expense (total gross)
    JournalEntryLine::create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => Account::where('code', '5100')->first()->id, // Payroll Expense
        'debit' => $period->total_gross,
        'credit' => 0,
    ]);

    // Credit: Payroll Payable (net pay)
    JournalEntryLine::create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => Account::where('code', '2100')->first()->id, // Payroll Payable
        'debit' => 0,
        'credit' => $period->total_net,
    ]);

    // Credit: Taxes Payable (income tax + social security)
    $totalTaxes = $period->total_gross - $period->total_net - $period->total_deductions;
    JournalEntryLine::create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => Account::where('code', '2110')->first()->id, // Taxes Payable
        'debit' => 0,
        'credit' => $totalTaxes,
    ]);

    $period->update(['journal_entry_id' => $journalEntry->id, 'status' => 'posted']);

    return $journalEntry;
}
```

#### 3.3 API Endpoints

```
GET    /api/v1/hr/payroll/periods           List payroll periods
POST   /api/v1/hr/payroll/periods           Create period
GET    /api/v1/hr/payroll/periods/{id}      Show period
PATCH  /api/v1/hr/payroll/periods/{id}/calculate   Calculate payroll
PATCH  /api/v1/hr/payroll/periods/{id}/approve     Approve payroll
POST   /api/v1/hr/payroll/periods/{id}/post        Post to accounting
PATCH  /api/v1/hr/payroll/periods/{id}/mark-paid   Mark as paid
GET    /api/v1/hr/payroll/items             List payroll items
GET    /api/v1/hr/employees/{id}/payroll-history   Employee payroll history
```

#### 3.4 Testing

Create 6 test files

**Test Scenarios:**
- Create payroll period
- Calculate gross pay (base + overtime)
- Calculate deductions (ISR, IMSS)
- Approve payroll
- Post to accounting (GL entry created)
- Mark as paid
- Employee payroll history

---

### Stage 4: Performance Reviews (Day 5, 3-4 hours)

#### 4.1 Database Migration

**Table: `hr_performance_reviews`**
```sql
CREATE TABLE hr_performance_reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    review_period_start DATE NOT NULL,
    review_period_end DATE NOT NULL,
    overall_rating INTEGER, -- 1-5
    goals_achievement INTEGER,
    skills_rating INTEGER,
    teamwork_rating INTEGER,
    attendance_rating INTEGER,
    strengths TEXT,
    areas_for_improvement TEXT,
    goals_for_next_period TEXT,
    reviewer_comments TEXT,
    employee_comments TEXT,
    status VARCHAR(50) DEFAULT 'draft', -- draft, completed, acknowledged
    completed_at TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (employee_id) REFERENCES hr_employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reviews_employee (employee_id),
    INDEX idx_reviews_status (status)
);
```

#### 4.2 Model & Endpoints

**PerformanceReview Model:**
```php
class PerformanceReview extends Model
{
    protected $table = 'hr_performance_reviews';

    protected $fillable = [
        'employee_id', 'reviewer_id', 'review_period_start', 'review_period_end',
        'overall_rating', 'goals_achievement', 'skills_rating', 'teamwork_rating',
        'attendance_rating', 'strengths', 'areas_for_improvement',
        'goals_for_next_period', 'reviewer_comments', 'employee_comments',
        'status', 'completed_at', 'acknowledged_at'
    ];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'completed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function employee(): BelongsTo;
    public function reviewer(): BelongsTo; // User
}
```

**Endpoints:**
```
GET    /api/v1/hr/reviews                   List reviews
POST   /api/v1/hr/reviews                   Create review
GET    /api/v1/hr/reviews/{id}              Show review
PATCH  /api/v1/hr/reviews/{id}              Update review
PATCH  /api/v1/hr/reviews/{id}/complete     Mark complete
PATCH  /api/v1/hr/reviews/{id}/acknowledge  Employee acknowledges
```

#### 4.3 Testing

Create 3 test files

**Test Scenarios:**
- Create performance review
- Complete review
- Employee acknowledgment
- Filter by employee/period

---

## Database Schema Summary

**New Tables:** 10
- `hr_departments` (9 columns, 2 indexes)
- `hr_positions` (11 columns, 2 indexes)
- `hr_employees` (38 columns, 7 indexes)
- `hr_attendances` (10 columns, 3 indexes + 1 unique)
- `hr_leave_types` (9 columns, 1 index)
- `hr_leaves` (12 columns, 3 indexes)
- `hr_payroll_periods` (12 columns, 2 indexes)
- `hr_payroll_items` (24 columns, 3 indexes)
- `hr_performance_reviews` (19 columns, 2 indexes)

**Integration:** Accounting module (journal_entries)

---

## API Endpoints Summary

| Entity | Endpoints |
|--------|-----------|
| Employees | 6 |
| Departments | 2 |
| Positions | 2 |
| Attendances | 3 |
| Leaves | 5 |
| Payroll Periods | 7 |
| Payroll Items | 2 |
| Performance Reviews | 6 |
| **TOTAL** | **33** |

---

## Testing Summary

| Entity | Test Files | Est. Tests |
|--------|-----------|------------|
| Employees | 8 | 40+ |
| Attendances | 3 | 15+ |
| Leaves | 3 | 15+ |
| Payroll | 6 | 30+ |
| Performance Reviews | 3 | 15+ |
| **TOTAL** | **23** | **115+** |

---

## Success Criteria

**Functional:**
- [ ] Employee CRUD with full details
- [ ] Attendance check-in/check-out
- [ ] Leave request/approval workflow
- [ ] Payroll calculation (gross + deductions)
- [ ] Payroll posting to GL
- [ ] Performance review workflow

**Technical:**
- [ ] 23+ test files, 115+ tests passing
- [ ] JSON:API 1.1 compliant
- [ ] Accounting integration working
- [ ] Proper authorization (HR manager, employee)
- [ ] API < 200ms (p95)

---

## Effort Breakdown

| Stage | Duration | Complexity |
|-------|----------|------------|
| Employee Management | 6-7 hours | Medium |
| Attendance & Leaves | 5-6 hours | Low-Medium |
| Payroll | 10-12 hours | High |
| Performance Reviews | 3-4 hours | Low |
| Testing & Integration | 4-5 hours | Medium |
| **TOTAL** | **28-34 hours** | **4-5 days** |

---

**Document Status:** Planning Complete
**Last Updated:** 2025-10-29
**Next Action:** Review and approve
