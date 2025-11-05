# HR Module - Frontend Integration Guide

**Module:** HR
**Entities:** 9 (Department, Position, Employee, Attendance, LeaveType, Leave, PayrollPeriod, PayrollItem, PerformanceReview)
**Endpoints:** 49
**Base Path:** `/api/v1`

## Overview

The HR module manages employee information, attendance tracking, leave management, payroll processing, and performance reviews. Payroll integrates with the Accounting module for automated GL posting.

## Core Entities

### 1. Employee

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

#### Relationships

- `department` → Department (belongsTo)
- `position` → Position (belongsTo)
- `user` → User (belongsTo)
- `attendances` → Attendance[] (hasMany)
- `leaves` → Leave[] (hasMany)
- `payrollItems` → PayrollItem[] (hasMany)
- `performanceReviews` → PerformanceReview[] (hasMany)

---

### 2. Attendance

**Endpoint:** `/attendances`
**Resource Type:** `attendances`

#### TypeScript Interface

```typescript
type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day';

interface Attendance {
  id: string;
  date: string;
  checkIn: string;  // HH:MM:SS format
  checkOut: string | null;
  hoursWorked: number;     // Auto-calculated
  overtimeHours: number;   // Auto-calculated
  status: AttendanceStatus;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}
```

**Auto-Calculated Fields:**
- `hoursWorked`: Automatically calculated from checkIn and checkOut times
- `overtimeHours`: Hours worked beyond standard work day (8 hours)

#### Relationships

- `employee` → Employee (belongsTo)

---

### 3. Leave

**Endpoint:** `/leaves`
**Resource Type:** `leaves`

#### TypeScript Interface

```typescript
type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

interface Leave {
  id: string;
  startDate: string;
  endDate: string;
  daysRequested: number;  // Auto-calculated
  status: LeaveStatus;
  reason: string;
  notes: string | null;
  approvedAt: string | null;
  createdAt: string;
  updatedAt: string;
}
```

**Auto-Calculated Fields:**
- `daysRequested`: Automatically calculated as business days between startDate and endDate

#### Relationships

- `employee` → Employee (belongsTo)
- `leaveType` → LeaveType (belongsTo)
- `approver` → Employee (belongsTo)

---

### 4. PayrollPeriod

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
```

**Auto-Calculated Fields:**
- `totalGross`: Sum of all payroll items' gross amounts
- `totalDeductions`: Sum of all payroll items' deductions
- `totalNet`: totalGross - totalDeductions

#### Relationships

- `payrollItems` → PayrollItem[] (hasMany)

---

### 5. PayrollItem

**Endpoint:** `/payroll-items`
**Resource Type:** `payroll-items`

#### TypeScript Interface

```typescript
interface PayrollItem {
  id: string;
  baseSalary: number;
  overtime: number;
  bonuses: number;
  grossPay: number;         // Auto-calculated
  socialSecurity: number;
  healthInsurance: number;
  taxes: number;
  otherDeductions: number;
  totalDeductions: number;  // Auto-calculated
  netPay: number;           // Auto-calculated
  status: string;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}
```

**Auto-Calculated Fields:**
- `grossPay`: baseSalary + overtime + bonuses
- `totalDeductions`: socialSecurity + healthInsurance + taxes + otherDeductions
- `netPay`: grossPay - totalDeductions

#### Relationships

- `employee` → Employee (belongsTo)
- `payrollPeriod` → PayrollPeriod (belongsTo)

---

### 6. PerformanceReview

**Endpoint:** `/performance-reviews`
**Resource Type:** `performance-reviews`

#### TypeScript Interface

```typescript
type ReviewStatus = 'draft' | 'pending' | 'completed';

interface PerformanceReview {
  id: string;
  reviewDate: string;
  periodStart: string;
  periodEnd: string;
  overallRating: number;  // 1-5
  strengths: string;
  areasForImprovement: string;
  goals: string;
  status: ReviewStatus;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `employee` → Employee (belongsTo)
- `reviewer` → Employee (belongsTo)

---

## Common Use Cases

### 1. Track Employee Attendance

```javascript
async function recordAttendance(employeeId, checkIn, checkOut = null) {
  const payload = {
    data: {
      type: "attendances",
      attributes: {
        employeeId: employeeId,
        date: new Date().toISOString().split('T')[0],
        checkIn: checkIn,      // "09:00:00"
        checkOut: checkOut,    // "17:30:00" or null
        status: "present"
      }
    }
  };

  const response = await fetch('/api/v1/attendances', {
    method: 'POST',
    headers,
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

```javascript
async function submitLeaveRequest(employeeId, leaveTypeId, startDate, endDate, reason) {
  const payload = {
    data: {
      type: "leaves",
      attributes: {
        employeeId: employeeId,
        leaveTypeId: leaveTypeId,
        startDate: startDate,
        endDate: endDate,
        status: "pending",
        reason: reason
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

```javascript
async function processPayroll(periodData) {
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
    '/api/v1/employees?filter[status]=active&include=attendances',
    { headers }
  );

  const employees = await employeesResponse.json();

  // 3. Create payroll items for each employee
  for (const employee of employees.data) {
    // Calculate overtime based on attendance
    const attendances = employee.relationships.attendances.data || [];
    const totalOvertime = attendances.reduce(
      (sum, att) => sum + (att.attributes.overtimeHours || 0),
      0
    );

    const itemPayload = {
      data: {
        type: "payroll-items",
        attributes: {
          payrollPeriodId: parseInt(periodId),
          employeeId: parseInt(employee.id),
          baseSalary: employee.attributes.salary,
          overtime: totalOvertime * 50, // $50 per hour
          bonuses: 0,
          socialSecurity: employee.attributes.salary * 0.0765,
          healthInsurance: 200,
          taxes: employee.attributes.salary * 0.15,
          otherDeductions: 0,
          status: "pending"
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
    totalNet: finalData.data.attributes.totalNet,
    employeeCount: employees.data.length
  };
}
```

### 4. Approve Payroll and Post to GL

```javascript
async function approveAndPostPayroll(payrollPeriodId) {
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

```javascript
async function getEmployeeSummary(employeeId) {
  const response = await fetch(
    `/api/v1/employees/${employeeId}?include=department,position,attendances,leaves,payrollItems`,
    { headers }
  );

  const data = await response.json();
  const employee = data.data;

  // Calculate attendance statistics
  const attendances = data.included.filter(inc => inc.type === 'attendances');
  const totalHours = attendances.reduce(
    (sum, att) => sum + (att.attributes.hoursWorked || 0),
    0
  );

  // Calculate leave balance
  const leaves = data.included.filter(inc => inc.type === 'leaves');
  const approvedDays = leaves
    .filter(leave => leave.attributes.status === 'approved')
    .reduce((sum, leave) => sum + leave.attributes.daysRequested, 0);

  // Get latest payroll
  const payrollItems = data.included.filter(inc => inc.type === 'payroll-items');
  const latestPayroll = payrollItems.sort(
    (a, b) => new Date(b.attributes.createdAt) - new Date(a.attributes.createdAt)
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
      pendingRequests: leaves.filter(l => l.attributes.status === 'pending').length
    },
    lastPayroll: latestPayroll ? {
      grossPay: latestPayroll.attributes.grossPay,
      netPay: latestPayroll.attributes.netPay,
      deductions: latestPayroll.attributes.totalDeductions
    } : null
  };
}
```

---

## Permissions

### Role-Based Access

| Role | Employees | Attendance | Leaves | Payroll | Reviews |
|------|-----------|------------|--------|---------|---------|
| **God** | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD |
| **Admin** | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD |
| **Tech** | ✅ Read | ✅ Read | ✅ Read | ✅ Read | ✅ Read |
| **Customer** | ❌ | ❌ | ❌ | ❌ | ❌ |

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
- PayrollItem: `grossPay`, `totalDeductions`, `netPay`
- PayrollPeriod: `totalGross`, `totalDeductions`, `totalNet`

**GL Integration:**
- Payroll automatically posts to General Ledger when approved
- Salaries Expense (DR), Salaries Payable (CR), Payroll Tax Payable (CR)

**Related Modules:**
- [Accounting Module](ACCOUNTING_FRONTEND_GUIDE.md) - GL posting for payroll
- User Module - Employee user accounts
