# HR Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Employee | `/api/v1/employees` | Employee records |
| Department | `/api/v1/departments` | Departments |
| Position | `/api/v1/positions` | Job positions |
| Attendance | `/api/v1/attendances` | Time tracking |
| Leave | `/api/v1/leaves` | Leave requests |
| LeaveType | `/api/v1/leave-types` | Leave types |
| PayrollItem | `/api/v1/payroll-items` | Payroll entries |
| PerformanceReview | `/api/v1/performance-reviews` | Reviews |

## Employee

```typescript
type EmployeeStatus = 'active' | 'inactive' | 'terminated' | 'on_leave';

interface Employee {
  id: string;
  employeeCode: string;      // EMP-001
  firstName: string;
  lastName: string;
  email: string;
  phone: string | null;
  departmentId: number;
  positionId: number;
  userId: number | null;     // Linked user account
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
}

// List employees
GET /api/v1/employees?filter[status]=active&include=department,position

// Get employee with all relations
GET /api/v1/employees/{id}?include=department,position,user,attendances,leaves

// Create employee
POST /api/v1/employees
{
  "data": {
    "type": "employees",
    "attributes": {
      "employeeCode": "EMP-001",
      "firstName": "Juan",
      "lastName": "Perez",
      "email": "juan.perez@company.com",
      "departmentId": 1,
      "positionId": 5,
      "hireDate": "2026-01-08",
      "salary": 25000.00,
      "status": "active"
    }
  }
}
```

### Filters

| Filter | Example |
|--------|---------|
| `filter[department_id]` | `?filter[department_id]=1` |
| `filter[position_id]` | `?filter[position_id]=5` |
| `filter[status]` | `?filter[status]=active` |
| `filter[hire_date]` | `?filter[hire_date]=2026-01-08` |

## Department

```typescript
interface Department {
  id: string;
  name: string;
  code: string;              // IT, HR, FIN
  description: string | null;
  managerId: number | null;  // Employee who manages
  isActive: boolean;
  createdAt: string;
}

// List departments
GET /api/v1/departments?include=manager,employees

// Create department
POST /api/v1/departments
{
  "data": {
    "type": "departments",
    "attributes": {
      "name": "Information Technology",
      "code": "IT",
      "managerId": 5,
      "isActive": true
    }
  }
}
```

## Position

```typescript
interface Position {
  id: string;
  departmentId: number;
  title: string;
  description: string | null;
  minSalary: number | null;
  maxSalary: number | null;
  isActive: boolean;
  createdAt: string;
}

// List positions
GET /api/v1/positions?filter[department_id]=1

// Create position
POST /api/v1/positions
{
  "data": {
    "type": "positions",
    "attributes": {
      "departmentId": 1,
      "title": "Senior Developer",
      "minSalary": 40000.00,
      "maxSalary": 60000.00,
      "isActive": true
    }
  }
}
```

## Attendance

```typescript
type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day' | 'remote';

interface Attendance {
  id: string;
  employeeId: number;
  date: string;
  checkIn: string | null;    // Time: "09:00:00"
  checkOut: string | null;
  status: AttendanceStatus;
  hoursWorked: number | null;
  overtimeHours: number | null;
  notes: string | null;
  createdAt: string;
}

// List attendance for employee
GET /api/v1/attendances?filter[employee_id]=1&filter[date][gte]=2026-01-01

// Clock in
POST /api/v1/attendances
{
  "data": {
    "type": "attendances",
    "attributes": {
      "employeeId": 1,
      "date": "2026-01-08",
      "checkIn": "09:00:00",
      "status": "present"
    }
  }
}

// Clock out (update)
PATCH /api/v1/attendances/{id}
{
  "data": {
    "type": "attendances",
    "id": "1",
    "attributes": {
      "checkOut": "18:00:00",
      "hoursWorked": 9.0
    }
  }
}
```

## Leave Request

```typescript
type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

interface Leave {
  id: string;
  employeeId: number;
  leaveTypeId: number;
  startDate: string;
  endDate: string;
  totalDays: number;
  reason: string;
  status: LeaveStatus;
  approverId: number | null;
  approvedAt: string | null;
  rejectionReason: string | null;
  createdAt: string;
}

// List leave requests
GET /api/v1/leaves?filter[status]=pending&include=employee,leaveType

// Create leave request
POST /api/v1/leaves
{
  "data": {
    "type": "leaves",
    "attributes": {
      "employeeId": 1,
      "leaveTypeId": 2,
      "startDate": "2026-01-15",
      "endDate": "2026-01-17",
      "totalDays": 3,
      "reason": "Family vacation"
    }
  }
}

// Approve leave (manager)
POST /api/v1/leaves/{id}/approve

// Reject leave
POST /api/v1/leaves/{id}/reject
{
  "reason": "Insufficient coverage during this period"
}
```

## Leave Type

```typescript
interface LeaveType {
  id: string;
  name: string;              // Vacation, Sick, Personal
  code: string;              // VAC, SICK, PER
  isPaid: boolean;
  maxDaysPerYear: number | null;
  requiresApproval: boolean;
  isActive: boolean;
}

// List leave types
GET /api/v1/leave-types

// Get employee leave balance
GET /api/v1/employees/{id}/leave-balance

// Response
{
  "balances": [
    { "leaveType": "Vacation", "entitled": 15, "used": 5, "remaining": 10 },
    { "leaveType": "Sick", "entitled": 10, "used": 2, "remaining": 8 }
  ]
}
```

## Payroll Item

```typescript
type PayrollType = 'salary' | 'bonus' | 'commission' | 'overtime' | 'deduction' | 'tax';

interface PayrollItem {
  id: string;
  employeeId: number;
  payrollPeriodId: number;
  type: PayrollType;
  description: string;
  amount: number;
  isDeduction: boolean;
  createdAt: string;
}

// List payroll items for employee
GET /api/v1/payroll-items?filter[employee_id]=1&filter[payroll_period_id]=12

// Get payroll summary
GET /api/v1/employees/{id}/payroll-summary?period_id=12

// Response
{
  "employee": { "id": 1, "name": "Juan Perez" },
  "period": { "id": 12, "name": "January 2026" },
  "earnings": [
    { "type": "salary", "amount": 25000.00 },
    { "type": "overtime", "amount": 2500.00 }
  ],
  "deductions": [
    { "type": "tax", "amount": 4500.00 },
    { "type": "insurance", "amount": 500.00 }
  ],
  "grossPay": 27500.00,
  "totalDeductions": 5000.00,
  "netPay": 22500.00
}
```

## Performance Review

```typescript
type ReviewStatus = 'draft' | 'submitted' | 'reviewed' | 'completed';

interface PerformanceReview {
  id: string;
  employeeId: number;
  reviewerId: number;
  reviewPeriod: string;      // "Q1 2026"
  status: ReviewStatus;
  overallRating: number | null;  // 1-5
  goals: Record<string, any>;
  achievements: string | null;
  areasForImprovement: string | null;
  comments: string | null;
  employeeComments: string | null;
  completedAt: string | null;
  createdAt: string;
}

// List reviews
GET /api/v1/performance-reviews?filter[employee_id]=1

// Create review
POST /api/v1/performance-reviews
{
  "data": {
    "type": "performance-reviews",
    "attributes": {
      "employeeId": 1,
      "reviewerId": 5,
      "reviewPeriod": "Q1 2026",
      "goals": {
        "goal1": { "description": "Complete project X", "weight": 30 },
        "goal2": { "description": "Improve skills", "weight": 20 }
      }
    }
  }
}

// Submit review
POST /api/v1/performance-reviews/{id}/submit

// Complete review
POST /api/v1/performance-reviews/{id}/complete
{
  "overall_rating": 4,
  "achievements": "Exceeded expectations...",
  "areas_for_improvement": "Communication skills"
}
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Leave Balance | Cannot request more than balance | Show remaining days |
| Leave Approval | Manager approval required | Show approval buttons |
| Attendance Auto | Calculate hours from check in/out | Auto-calculate |
| Salary Range | Position has min/max salary | Validate on create |
| Active Employee | Only active can clock in | Filter by status |

## Employee Self-Service

```typescript
// Get my profile
GET /api/v1/employees/me

// My attendance this month
GET /api/v1/employees/me/attendances?month=2026-01

// My leave balance
GET /api/v1/employees/me/leave-balance

// My payslips
GET /api/v1/employees/me/payslips

// Clock in
POST /api/v1/employees/me/clock-in

// Clock out
POST /api/v1/employees/me/clock-out
```
