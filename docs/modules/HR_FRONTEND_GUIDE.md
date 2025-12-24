# HR Module - Frontend Integration Guide

**Module:** HR
**Entities:** 9 (Department, Position, Employee, Attendance, LeaveType, Leave, PayrollPeriod, PayrollItem, PerformanceReview)
**Endpoints:** 49
**Base Path:** `/api/v1`

## Overview

The HR module manages employee information, attendance tracking, leave management, payroll processing, and performance reviews. Payroll integrates with the Accounting module for automated GL posting.

## Core Entities

### 1. Department

**Endpoint:** `/departments`
**Resource Type:** `departments`

#### TypeScript Interface

```typescript
interface Department {
  id: string;
  name: string;
  description: string | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

interface DepartmentCreateRequest {
  name: string;
  description?: string;
  isActive?: boolean;
  managerId?: string;  // relationship to Employee
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | Yes |
| `description` | `description` | string | No | Yes | No |
| `isActive` | `is_active` | boolean | No | Yes | Yes |

#### Relationships

- `manager` → Employee (belongsTo) - Department manager
- `employees` → Employee[] (hasMany, readOnly)
- `positions` → Position[] (hasMany, readOnly)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[name]` | `Engineering` | Filter by name |
| `filter[isActive]` | `true` | Filter by active status |
| `filter[managerId]` | `5` | Filter by manager |

---

### 2. Position

**Endpoint:** `/positions`
**Resource Type:** `positions`

#### TypeScript Interface

```typescript
type PositionLevel = 'junior' | 'mid' | 'senior' | 'lead' | 'manager' | 'director';

interface Position {
  id: string;
  title: string;
  description: string | null;
  level: PositionLevel;
  minSalary: number;
  maxSalary: number;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

interface PositionCreateRequest {
  title: string;
  description?: string;
  level?: PositionLevel;
  minSalary?: number;
  maxSalary?: number;
  isActive?: boolean;
  departmentId?: string;  // relationship
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `title` | `title` | string | Yes | Yes | Yes |
| `description` | `description` | string | No | Yes | No |
| `level` | `level` | string | No | Yes | Yes |
| `minSalary` | `min_salary` | number | No | Yes | No |
| `maxSalary` | `max_salary` | number | No | Yes | No |
| `isActive` | `is_active` | boolean | No | Yes | Yes |

#### Relationships

- `department` → Department (belongsTo)
- `employees` → Employee[] (hasMany, readOnly)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[title]` | `Developer` | Filter by title |
| `filter[level]` | `senior` | Filter by level |
| `filter[isActive]` | `true` | Filter by active status |
| `filter[departmentId]` | `3` | Filter by department |

---

### 3. Employee

**Endpoint:** `/employees`
**Resource Type:** `employees`

#### TypeScript Interface

```typescript
type EmployeeStatus = 'active' | 'inactive' | 'terminated';

interface Employee {
  id: string;
  employeeCode: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string | null;
  hireDate: string;
  birthDate: string | null;
  salary: number;
  status: EmployeeStatus;
  terminationDate: string | null;
  terminationReason: string | null;
  address: string | null;
  emergencyContactName: string | null;
  emergencyContactPhone: string | null;
  createdAt: string;
  updatedAt: string;
}

interface EmployeeCreateRequest {
  employeeCode: string;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  hireDate: string;
  birthDate?: string;
  salary: number;
  status?: EmployeeStatus;
  address?: string;
  emergencyContactName?: string;
  emergencyContactPhone?: string;
  departmentId?: string;  // relationship
  positionId?: string;    // relationship
  userId?: string;        // relationship
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `employeeCode` | `employee_code` | string | Yes | Yes | Yes |
| `firstName` | `first_name` | string | Yes | Yes | Yes |
| `lastName` | `last_name` | string | Yes | Yes | Yes |
| `email` | `email` | string | Yes | Yes | Yes |
| `phone` | `phone` | string | No | Yes | No |
| `hireDate` | `hire_date` | date | Yes | Yes | No |
| `birthDate` | `birth_date` | date | No | Yes | No |
| `salary` | `salary` | number | Yes | Yes | No |
| `status` | `status` | string | Yes | Yes | Yes |
| `terminationDate` | `termination_date` | date | No | Yes | No |
| `terminationReason` | `termination_reason` | string | No | No | No |
| `address` | `address` | string | No | No | No |
| `emergencyContactName` | `emergency_contact_name` | string | No | No | No |
| `emergencyContactPhone` | `emergency_contact_phone` | string | No | No | No |

#### Relationships

- `department` → Department (belongsTo)
- `position` → Position (belongsTo)
- `user` → User (belongsTo, readOnly)
- `managedDepartments` → Department[] (hasMany, readOnly)
- `attendances` → Attendance[] (hasMany, readOnly)
- `leaves` → Leave[] (hasMany, readOnly)
- `payrollItems` → PayrollItem[] (hasMany, readOnly)
- `performanceReviews` → PerformanceReview[] (hasMany, readOnly)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[employeeCode]` | `EMP001` | Filter by code |
| `filter[firstName]` | `John` | Filter by first name |
| `filter[lastName]` | `Doe` | Filter by last name |
| `filter[email]` | `john@example.com` | Filter by email |
| `filter[status]` | `active` | Filter by status |
| `filter[departmentId]` | `2` | Filter by department |
| `filter[positionId]` | `5` | Filter by position |
| `filter[userId]` | `1` | Filter by linked user |

---

### 4. Attendance

**Endpoint:** `/attendances`
**Resource Type:** `attendances`

#### TypeScript Interface

```typescript
type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day';

interface Attendance {
  id: string;
  date: string;
  checkIn: string;           // HH:MM:SS format
  checkOut: string | null;   // HH:MM:SS format
  hoursWorked: number;       // Auto-calculated
  overtimeHours: number;     // Auto-calculated
  status: AttendanceStatus;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}

interface AttendanceCreateRequest {
  date: string;
  checkIn: string;
  checkOut?: string;
  status: AttendanceStatus;
  notes?: string;
  employeeId: string;  // relationship (required)
}
```

**Auto-Calculated Fields:**
- `hoursWorked`: Automatically calculated from checkIn and checkOut times
- `overtimeHours`: Hours worked beyond standard work day (8 hours)

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `date` | `date` | date | Yes | Yes | Yes |
| `checkIn` | `check_in` | string | Yes | Yes | No |
| `checkOut` | `check_out` | string | No | Yes | No |
| `hoursWorked` | `hours_worked` | number | No | Yes | No |
| `overtimeHours` | `overtime_hours` | number | No | Yes | No |
| `status` | `status` | string | Yes | Yes | Yes |
| `notes` | `notes` | string | No | No | No |

#### Relationships

- `employee` → Employee (belongsTo, required)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[date]` | `2024-01-15` | Filter by date |
| `filter[status]` | `present` | Filter by status |
| `filter[employee]` | `5` | Filter by employee ID |
| `filter[employeeId]` | `5` | Filter by employee ID (alternative) |

---

### 5. LeaveType

**Endpoint:** `/leave-types`
**Resource Type:** `leave-types`

#### TypeScript Interface

```typescript
interface LeaveType {
  id: string;
  name: string;
  code: string;
  description: string | null;
  daysAllowed: number;
  requiresApproval: boolean;
  paid: boolean;
  active: boolean;
  createdAt: string;
  updatedAt: string;
}

interface LeaveTypeCreateRequest {
  name: string;
  code: string;
  description?: string;
  daysAllowed: number;
  requiresApproval?: boolean;
  paid?: boolean;
  active?: boolean;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | No |
| `code` | `code` | string | Yes | Yes | Yes |
| `description` | `description` | string | No | No | No |
| `daysAllowed` | `days_allowed` | number | Yes | Yes | No |
| `requiresApproval` | `requires_approval` | boolean | No | Yes | Yes |
| `paid` | `paid` | boolean | No | Yes | Yes |
| `active` | `active` | boolean | No | Yes | Yes |

#### Relationships

- `leaves` → Leave[] (hasMany)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[code]` | `VAC` | Filter by code |
| `filter[active]` | `true` | Filter by active status |
| `filter[paid]` | `true` | Filter by paid leave |
| `filter[requiresApproval]` | `true` | Filter by approval requirement |

---

### 6. Leave

**Endpoint:** `/leaves`
**Resource Type:** `leaves`

#### TypeScript Interface

```typescript
type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

interface Leave {
  id: string;
  startDate: string;
  endDate: string;
  daysRequested: number;    // Auto-calculated
  status: LeaveStatus;
  reason: string;
  notes: string | null;
  approvedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

interface LeaveCreateRequest {
  startDate: string;
  endDate: string;
  status?: LeaveStatus;
  reason: string;
  notes?: string;
  employeeId: string;   // relationship (required)
  leaveTypeId: string;  // relationship (required)
  approverId?: string;  // relationship
}
```

**Auto-Calculated Fields:**
- `daysRequested`: Automatically calculated as business days between startDate and endDate

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `startDate` | `start_date` | date | Yes | Yes | Yes |
| `endDate` | `end_date` | date | Yes | Yes | Yes |
| `daysRequested` | `days_requested` | number | No | Yes | No |
| `status` | `status` | string | No | Yes | Yes |
| `reason` | `reason` | string | Yes | No | No |
| `notes` | `notes` | string | No | No | No |
| `approvedAt` | `approved_at` | datetime | No | Yes | No |

#### Relationships

- `employee` → Employee (belongsTo, required)
- `leaveType` → LeaveType (belongsTo, required)
- `approver` → Employee (belongsTo)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[status]` | `pending` | Filter by status |
| `filter[employeeId]` | `5` | Filter by employee |
| `filter[leaveTypeId]` | `2` | Filter by leave type |
| `filter[startDate]` | `2024-01-01` | Filter by start date |
| `filter[endDate]` | `2024-01-31` | Filter by end date |

---

### 7. PayrollPeriod

**Endpoint:** `/payroll-periods`
**Resource Type:** `payroll-periods`

#### TypeScript Interface

```typescript
type PeriodType = 'weekly' | 'biweekly' | 'monthly';
type PayrollStatus = 'draft' | 'processing' | 'approved' | 'paid' | 'closed';

interface PayrollPeriod {
  id: string;
  name: string;
  periodType: PeriodType;
  startDate: string;
  endDate: string;
  paymentDate: string;
  status: PayrollStatus;
  totalGross: number;       // Auto-calculated
  totalDeductions: number;  // Auto-calculated
  totalNet: number;         // Auto-calculated
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}

interface PayrollPeriodCreateRequest {
  name: string;
  periodType: PeriodType;
  startDate: string;
  endDate: string;
  paymentDate: string;
  status?: PayrollStatus;
  notes?: string;
}
```

**Auto-Calculated Fields:**
- `totalGross`: Sum of all payroll items' (basicSalary + overtimePay + bonuses)
- `totalDeductions`: Sum of all payroll items' deductions
- `totalNet`: totalGross - totalDeductions

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `name` | `name` | string | Yes | Yes | No |
| `periodType` | `period_type` | string | Yes | Yes | Yes |
| `startDate` | `start_date` | date | Yes | Yes | Yes |
| `endDate` | `end_date` | date | Yes | Yes | Yes |
| `paymentDate` | `payment_date` | date | Yes | Yes | Yes |
| `status` | `status` | string | No | Yes | Yes |
| `totalGross` | `total_gross` | number | No | Yes | No |
| `totalDeductions` | `total_deductions` | number | No | Yes | No |
| `totalNet` | `total_net` | number | No | Yes | No |
| `notes` | `notes` | string | No | No | No |

#### Relationships

- `payrollItems` → PayrollItem[] (hasMany)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[status]` | `approved` | Filter by status |
| `filter[periodType]` | `monthly` | Filter by period type |
| `filter[startDate]` | `2024-01-01` | Filter by start date |
| `filter[endDate]` | `2024-01-31` | Filter by end date |
| `filter[paymentDate]` | `2024-02-05` | Filter by payment date |

---

### 8. PayrollItem

**Endpoint:** `/payroll-items`
**Resource Type:** `payroll-items`

#### TypeScript Interface

```typescript
type PayrollItemStatus = 'draft' | 'pending' | 'paid';

interface PayrollItem {
  id: string;
  basicSalary: number;
  overtimePay: number;
  bonuses: number;
  deductions: number;
  netPay: number;           // Auto-calculated
  status: PayrollItemStatus;
  paidAt: string | null;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}

interface PayrollItemCreateRequest {
  basicSalary: number;
  overtimePay?: number;
  bonuses?: number;
  deductions?: number;
  status?: PayrollItemStatus;
  paidAt?: string;
  notes?: string;
  employeeId: string;       // relationship (required)
  payrollPeriodId: string;  // relationship (required)
}
```

**Auto-Calculated Fields:**
- `netPay`: (basicSalary + overtimePay + bonuses) - deductions

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `basicSalary` | `basic_salary` | number | Yes | Yes | No |
| `overtimePay` | `overtime_pay` | number | No | Yes | No |
| `bonuses` | `bonuses` | number | No | Yes | No |
| `deductions` | `deductions` | number | No | Yes | No |
| `netPay` | `net_pay` | number | No | Yes | No |
| `status` | `status` | string | No | Yes | Yes |
| `paidAt` | `paid_at` | datetime | No | Yes | No |
| `notes` | `notes` | string | No | No | No |

#### Relationships

- `employee` → Employee (belongsTo, required)
- `payrollPeriod` → PayrollPeriod (belongsTo, required)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[status]` | `pending` | Filter by status |
| `filter[employeeId]` | `5` | Filter by employee |
| `filter[payrollPeriodId]` | `3` | Filter by payroll period |

---

### 9. PerformanceReview

**Endpoint:** `/performance-reviews`
**Resource Type:** `performance-reviews`

#### TypeScript Interface

```typescript
type ReviewStatus = 'draft' | 'submitted' | 'reviewed' | 'acknowledged';

interface PerformanceReview {
  id: string;
  reviewDate: string;
  reviewPeriodStart: string;
  reviewPeriodEnd: string;
  overallRating: number;      // 1-5
  goalsRating: number;        // 1-5
  skillsRating: number;       // 1-5
  attendanceRating: number;   // 1-5
  comments: string | null;
  employeeComments: string | null;
  status: ReviewStatus;
  createdAt: string;
  updatedAt: string;
}

interface PerformanceReviewCreateRequest {
  reviewDate: string;
  reviewPeriodStart: string;
  reviewPeriodEnd: string;
  overallRating: number;
  goalsRating?: number;
  skillsRating?: number;
  attendanceRating?: number;
  comments?: string;
  employeeComments?: string;
  status?: ReviewStatus;
  employeeId: string;   // relationship (required)
  reviewerId: string;   // relationship (required)
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `reviewDate` | `review_date` | date | Yes | Yes | Yes |
| `reviewPeriodStart` | `review_period_start` | date | Yes | Yes | No |
| `reviewPeriodEnd` | `review_period_end` | date | Yes | Yes | No |
| `overallRating` | `overall_rating` | integer | Yes | Yes | No |
| `goalsRating` | `goals_rating` | integer | No | Yes | No |
| `skillsRating` | `skills_rating` | integer | No | Yes | No |
| `attendanceRating` | `attendance_rating` | integer | No | Yes | No |
| `comments` | `comments` | string | No | No | No |
| `employeeComments` | `employee_comments` | string | No | No | No |
| `status` | `status` | string | No | Yes | Yes |

#### Relationships

- `employee` → Employee (belongsTo, required)
- `reviewer` → Employee (belongsTo, required)

#### Filters

| Filter | Example | Description |
|--------|---------|-------------|
| `filter[status]` | `reviewed` | Filter by status |
| `filter[employeeId]` | `5` | Filter by employee |
| `filter[reviewerId]` | `3` | Filter by reviewer |
| `filter[reviewDate]` | `2024-06-15` | Filter by review date |

---

## Common Use Cases

### 1. Track Employee Attendance

```typescript
async function recordAttendance(employeeId: string, checkIn: string, checkOut?: string) {
  const payload = {
    data: {
      type: "attendances",
      attributes: {
        date: new Date().toISOString().split('T')[0],
        checkIn: checkIn,      // "09:00:00"
        checkOut: checkOut,    // "17:30:00" or null
        status: "present"
      },
      relationships: {
        employee: {
          data: { type: "employees", id: employeeId }
        }
      }
    }
  };

  const response = await fetch('/api/v1/attendances', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/vnd.api+json',
      'Accept': 'application/vnd.api+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(payload)
  });

  const attendance = await response.json();

  // Fields hoursWorked and overtimeHours are auto-calculated
  console.log('Hours worked:', attendance.data.attributes.hoursWorked);
  console.log('Overtime hours:', attendance.data.attributes.overtimeHours);

  return attendance;
}
```

### 2. Submit Leave Request

```typescript
async function submitLeaveRequest(
  employeeId: string,
  leaveTypeId: string,
  startDate: string,
  endDate: string,
  reason: string
) {
  const payload = {
    data: {
      type: "leaves",
      attributes: {
        startDate: startDate,
        endDate: endDate,
        status: "pending",
        reason: reason
      },
      relationships: {
        employee: {
          data: { type: "employees", id: employeeId }
        },
        leaveType: {
          data: { type: "leave-types", id: leaveTypeId }
        }
      }
    }
  };

  const response = await fetch('/api/v1/leaves', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  const leave = await response.json();

  // Field daysRequested is auto-calculated
  console.log('Days requested:', leave.data.attributes.daysRequested);

  return leave;
}
```

### 3. Process Payroll

```typescript
async function processPayroll(periodData: {
  name: string;
  startDate: string;
  endDate: string;
  paymentDate: string;
}) {
  // 1. Create payroll period
  const periodPayload = {
    data: {
      type: "payroll-periods",
      attributes: {
        name: periodData.name,
        periodType: "monthly",
        startDate: periodData.startDate,
        endDate: periodData.endDate,
        paymentDate: periodData.paymentDate,
        status: "draft"
      }
    }
  };

  const periodResponse = await fetch('/api/v1/payroll-periods', {
    method: 'POST',
    headers,
    body: JSON.stringify(periodPayload)
  });

  const period = await periodResponse.json();
  const periodId = period.data.id;

  // 2. Get active employees
  const employeesResponse = await fetch(
    '/api/v1/employees?filter[status]=active',
    { headers }
  );

  const employees = await employeesResponse.json();

  // 3. Create payroll items for each employee
  for (const employee of employees.data) {
    const itemPayload = {
      data: {
        type: "payroll-items",
        attributes: {
          basicSalary: employee.attributes.salary,
          overtimePay: 0,
          bonuses: 0,
          deductions: employee.attributes.salary * 0.15, // Example: 15% deductions
          status: "pending"
        },
        relationships: {
          payrollPeriod: {
            data: { type: "payroll-periods", id: periodId }
          },
          employee: {
            data: { type: "employees", id: employee.id }
          }
        }
      }
    };

    await fetch('/api/v1/payroll-items', {
      method: 'POST',
      headers,
      body: JSON.stringify(itemPayload)
    });
  }

  // 4. Get updated period with totals (auto-calculated)
  const finalPeriod = await fetch(
    `/api/v1/payroll-periods/${periodId}?include=payrollItems`,
    { headers }
  );

  const finalData = await finalPeriod.json();

  return {
    periodId: periodId,
    totalGross: finalData.data.attributes.totalGross,
    totalDeductions: finalData.data.attributes.totalDeductions,
    totalNet: finalData.data.attributes.totalNet,
    employeeCount: employees.data.length
  };
}
```

### 4. Approve Payroll and Post to GL

```typescript
async function approveAndPostPayroll(payrollPeriodId: string) {
  // 1. Approve payroll period
  const approvePayload = {
    data: {
      type: "payroll-periods",
      id: payrollPeriodId,
      attributes: {
        status: "approved"
      }
    }
  };

  await fetch(`/api/v1/payroll-periods/${payrollPeriodId}`, {
    method: 'PATCH',
    headers,
    body: JSON.stringify(approvePayload)
  });

  // 2. Get payroll details
  const periodResponse = await fetch(
    `/api/v1/payroll-periods/${payrollPeriodId}?include=payrollItems`,
    { headers }
  );

  const period = await periodResponse.json();

  // PayrollService automatically posts to GL:
  // DR: Salaries Expense    totalGross
  // CR: Salaries Payable    totalNet
  // CR: Payroll Tax Payable totalDeductions

  console.log('Payroll posted to GL automatically');
  console.log('Total Expense:', period.data.attributes.totalGross);

  return period;
}
```

### 5. Get Employee Summary

```typescript
async function getEmployeeSummary(employeeId: string) {
  const response = await fetch(
    `/api/v1/employees/${employeeId}?include=department,position,attendances,leaves,payrollItems`,
    { headers }
  );

  const data = await response.json();
  const employee = data.data;

  // Calculate attendance statistics
  const attendances = data.included?.filter((inc: any) => inc.type === 'attendances') || [];
  const totalHours = attendances.reduce(
    (sum: number, att: any) => sum + (att.attributes.hoursWorked || 0),
    0
  );

  // Calculate leave balance
  const leaves = data.included?.filter((inc: any) => inc.type === 'leaves') || [];
  const approvedDays = leaves
    .filter((leave: any) => leave.attributes.status === 'approved')
    .reduce((sum: number, leave: any) => sum + leave.attributes.daysRequested, 0);

  // Get latest payroll
  const payrollItems = data.included?.filter((inc: any) => inc.type === 'payroll-items') || [];
  const latestPayroll = payrollItems.sort(
    (a: any, b: any) => new Date(b.attributes.createdAt).getTime() - new Date(a.attributes.createdAt).getTime()
  )[0];

  return {
    employee: {
      name: `${employee.attributes.firstName} ${employee.attributes.lastName}`,
      code: employee.attributes.employeeCode,
      status: employee.attributes.status,
      salary: employee.attributes.salary
    },
    attendance: {
      totalHours: totalHours,
      records: attendances.length
    },
    leaves: {
      approvedDays: approvedDays,
      pendingRequests: leaves.filter((l: any) => l.attributes.status === 'pending').length
    },
    lastPayroll: latestPayroll ? {
      basicSalary: latestPayroll.attributes.basicSalary,
      netPay: latestPayroll.attributes.netPay,
      deductions: latestPayroll.attributes.deductions
    } : null
  };
}
```

### 6. Create Performance Review

```typescript
async function createPerformanceReview(
  employeeId: string,
  reviewerId: string,
  ratings: {
    overall: number;
    goals: number;
    skills: number;
    attendance: number;
  },
  comments: string
) {
  const today = new Date();
  const sixMonthsAgo = new Date(today);
  sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);

  const payload = {
    data: {
      type: "performance-reviews",
      attributes: {
        reviewDate: today.toISOString().split('T')[0],
        reviewPeriodStart: sixMonthsAgo.toISOString().split('T')[0],
        reviewPeriodEnd: today.toISOString().split('T')[0],
        overallRating: ratings.overall,
        goalsRating: ratings.goals,
        skillsRating: ratings.skills,
        attendanceRating: ratings.attendance,
        comments: comments,
        status: "draft"
      },
      relationships: {
        employee: {
          data: { type: "employees", id: employeeId }
        },
        reviewer: {
          data: { type: "employees", id: reviewerId }
        }
      }
    }
  };

  const response = await fetch('/api/v1/performance-reviews', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return response.json();
}
```

---

## Permissions

### Role-Based Access

| Role | Employees | Attendance | Leaves | Payroll | Reviews | Departments | Positions | LeaveTypes |
|------|-----------|------------|--------|---------|---------|-------------|-----------|------------|
| **God** | CRUD | CRUD | CRUD | CRUD | CRUD | CRUD | CRUD | CRUD |
| **Admin** | CRUD | CRUD | CRUD | CRUD | CRUD | CRUD | CRUD | CRUD |
| **Tech** | Read | Read | Read | Read | Read | Read | Read | Read |
| **Customer** | - | - | - | - | - | - | - | - |

### Permission Names

| Entity | index | show | store | update | destroy |
|--------|-------|------|-------|--------|---------|
| employees | `employees.index` | `employees.show` | `employees.store` | `employees.update` | `employees.destroy` |
| attendances | `attendances.index` | `attendances.show` | `attendances.store` | `attendances.update` | `attendances.destroy` |
| leaves | `leaves.index` | `leaves.show` | `leaves.store` | `leaves.update` | `leaves.destroy` |
| leave-types | `leave-types.index` | `leave-types.show` | `leave-types.store` | `leave-types.update` | `leave-types.destroy` |
| payroll-periods | `payroll-periods.index` | `payroll-periods.show` | `payroll-periods.store` | `payroll-periods.update` | `payroll-periods.destroy` |
| payroll-items | `payroll-items.index` | `payroll-items.show` | `payroll-items.store` | `payroll-items.update` | `payroll-items.destroy` |
| performance-reviews | `performance-reviews.index` | `performance-reviews.show` | `performance-reviews.store` | `performance-reviews.update` | `performance-reviews.destroy` |
| departments | `departments.index` | `departments.show` | `departments.store` | `departments.update` | `departments.destroy` |
| positions | `positions.index` | `positions.show` | `positions.store` | `positions.update` | `positions.destroy` |

---

## Quick Reference

**Available Endpoints:**
- `/departments`, `/positions` - Organizational structure
- `/employees` - Employee management
- `/attendances` - Time tracking (auto-calculates hours)
- `/leave-types`, `/leaves` - Leave management (auto-calculates days)
- `/payroll-periods`, `/payroll-items` - Payroll (auto-calculates totals)
- `/performance-reviews` - Performance management

**Auto-Calculated Fields:**
- Attendance: `hoursWorked`, `overtimeHours`
- Leave: `daysRequested`
- PayrollItem: `netPay` (basicSalary + overtimePay + bonuses - deductions)
- PayrollPeriod: `totalGross`, `totalDeductions`, `totalNet`

**GL Integration:**
- Payroll automatically posts to General Ledger when approved
- Salaries Expense (DR), Salaries Payable (CR), Payroll Tax Payable (CR)

**Related Modules:**
- [Accounting Module](ACCOUNTING_FRONTEND_GUIDE.md) - GL posting for payroll
- User Module - Employee user accounts
