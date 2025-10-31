# Phase 4.4 - HR Module - Implementation Plan v2.0

**Status:** 📋 Planning
**Start Date:** 2025-10-31
**Estimated Duration:** 4-5 days
**Complexity:** Medium (3/5)
**Priority:** High
**Dependencies:**
- Accounting Module ✅ Complete (for payroll GL posting)
- User Module ✅ Complete (for employee-user linking)

---

## 🔴 LESSONS LEARNED FROM PHASE 4 (ERRORS TO AVOID)

### Critical Issues from Phase 4.1-4.3:

1. ❌ **Controllers without Actions traits** → Used JsonResponse directly
   - ✅ **FIX:** ALL controllers MUST use Actions traits (FetchMany, FetchOne, Store, Update, Destroy)

2. ❌ **Incomplete Schemas** → Missing fields, filters, or pagination
   - ✅ **FIX:** Every Schema MUST have: fields(), filters(), pagination()

3. ❌ **Incomplete Authorizers** → Missing 5 relationship methods
   - ✅ **FIX:** ALL Authorizers MUST implement 10 methods (5 CRUD + 5 relationships)

4. ❌ **Double-seeding in tests** → RefreshDatabase + setUp() seeding
   - ✅ **FIX:** Remove RefreshDatabase trait, rely on TestCase base seeding

5. ❌ **Missing permissions in seeder** → Had to add them later
   - ✅ **FIX:** Create PermissionsSeeder FIRST with all permissions upfront

6. ❌ **Incomplete factory states** → No helper methods
   - ✅ **FIX:** Add useful state methods (active(), terminated(), etc.)

7. ❌ **Validation without Spanish** → English error messages
   - ✅ **FIX:** ALL validation messages in Spanish

8. ❌ **Missing model fields** → Had to migrate fields later
   - ✅ **FIX:** Plan complete schema upfront, add ALL fields from start

---

## 📋 MODULE STRUCTURE OVERVIEW

```
Modules/HR/
├── app/
│   ├── Http/Controllers/Api/V1/
│   │   ├── EmployeeController.php            ✅ Actions traits
│   │   ├── DepartmentController.php          ✅ Actions traits
│   │   ├── PositionController.php            ✅ Actions traits
│   │   ├── AttendanceController.php          ✅ Actions traits
│   │   ├── LeaveController.php               ✅ Actions traits
│   │   ├── LeaveTypeController.php           ✅ Actions traits
│   │   ├── PayrollPeriodController.php       ✅ Actions traits
│   │   ├── PayrollItemController.php         ✅ Actions traits
│   │   └── PerformanceReviewController.php   ✅ Actions traits
│   ├── JsonApi/V1/
│   │   ├── Employees/
│   │   │   ├── EmployeeSchema.php            ✅ Complete
│   │   │   ├── EmployeeAuthorizer.php        ✅ 10 methods
│   │   │   ├── EmployeeRequest.php           ✅ Spanish
│   │   │   └── EmployeeResource.php          ✅ Complete
│   │   ├── Departments/                      ✅ Complete
│   │   ├── Positions/                        ✅ Complete
│   │   ├── Attendances/                      ✅ Complete
│   │   ├── Leaves/                           ✅ Complete
│   │   ├── LeaveTypes/                       ✅ Complete
│   │   ├── PayrollPeriods/                   ✅ Complete
│   │   ├── PayrollItems/                     ✅ Complete
│   │   └── PerformanceReviews/               ✅ Complete
│   ├── Models/
│   │   ├── Employee.php                      ✅ + scopes
│   │   ├── Department.php                    ✅ + scopes
│   │   ├── Position.php                      ✅ + scopes
│   │   ├── Attendance.php                    ✅ + auto-calc
│   │   ├── Leave.php                         ✅ + scopes
│   │   ├── LeaveType.php                     ✅ + scopes
│   │   ├── PayrollPeriod.php                 ✅ + scopes
│   │   ├── PayrollItem.php                   ✅ + calculations
│   │   └── PerformanceReview.php             ✅ + scopes
│   └── Services/
│       ├── AttendanceService.php             ✅ Business logic
│       ├── LeaveService.php                  ✅ Business logic
│       └── PayrollService.php                ✅ + GL posting
├── Database/
│   ├── factories/
│   │   ├── EmployeeFactory.php               ✅ + states
│   │   ├── DepartmentFactory.php             ✅ + states
│   │   ├── PositionFactory.php               ✅ + states
│   │   ├── AttendanceFactory.php             ✅ + states
│   │   ├── LeaveFactory.php                  ✅ + states
│   │   ├── LeaveTypeFactory.php              ✅ + states
│   │   ├── PayrollPeriodFactory.php          ✅ + states
│   │   ├── PayrollItemFactory.php            ✅ + states
│   │   └── PerformanceReviewFactory.php      ✅ + states
│   ├── migrations/
│   │   ├── *_create_hr_departments_table.php
│   │   ├── *_create_hr_positions_table.php
│   │   ├── *_create_hr_employees_table.php
│   │   ├── *_create_hr_attendances_table.php
│   │   ├── *_create_hr_leave_types_table.php
│   │   ├── *_create_hr_leaves_table.php
│   │   ├── *_create_hr_payroll_periods_table.php
│   │   ├── *_create_hr_payroll_items_table.php
│   │   └── *_create_hr_performance_reviews_table.php
│   └── seeders/
│       ├── HRDatabaseSeeder.php              ✅ Main seeder
│       ├── PermissionsSeeder.php             ✅ 45 permissions
│       ├── LeaveTypeSeeder.php               ✅ Default types
│       └── DepartmentSeeder.php              ✅ Sample data
└── tests/Feature/
    ├── Employee*Test.php (5 files)           ✅ No RefreshDatabase
    ├── Department*Test.php (5 files)         ✅ No RefreshDatabase
    ├── Position*Test.php (5 files)           ✅ No RefreshDatabase
    ├── Attendance*Test.php (5 files)         ✅ No RefreshDatabase
    ├── Leave*Test.php (5 files)              ✅ No RefreshDatabase
    ├── LeaveType*Test.php (5 files)          ✅ No RefreshDatabase
    ├── PayrollPeriod*Test.php (5 files)      ✅ No RefreshDatabase
    ├── PayrollItem*Test.php (5 files)        ✅ No RefreshDatabase
    └── PerformanceReview*Test.php (5 files)  ✅ No RefreshDatabase
```

**Total Files:** 90+ files
**Total Entities:** 9 entities
**Total Endpoints:** 45 endpoints (5 per entity)
**Total Permissions:** 45 permissions (5 per entity)
**Total Tests:** 45 test files (5 per entity)

---

## 🎯 IMPLEMENTATION PHASES

### ✅ Phase 0: Setup & Permissions (2 hours)

**Goal:** Create module structure and permissions FIRST

#### 0.1 Create Module
```bash
php artisan module:make HR
```

#### 0.2 Create ALL Permissions First (before any code)
```php
// Modules/HR/Database/seeders/PermissionsSeeder.php

$permissions = [
    // Employees (5)
    'hr.employees.index',
    'hr.employees.show',
    'hr.employees.store',
    'hr.employees.update',
    'hr.employees.destroy',

    // Departments (5)
    'hr.departments.index',
    'hr.departments.show',
    'hr.departments.store',
    'hr.departments.update',
    'hr.departments.destroy',

    // Positions (5)
    'hr.positions.index',
    'hr.positions.show',
    'hr.positions.store',
    'hr.positions.update',
    'hr.positions.destroy',

    // Attendances (5)
    'hr.attendances.index',
    'hr.attendances.show',
    'hr.attendances.store',
    'hr.attendances.update',
    'hr.attendances.destroy',

    // Leaves (5)
    'hr.leaves.index',
    'hr.leaves.show',
    'hr.leaves.store',
    'hr.leaves.update',
    'hr.leaves.destroy',

    // Leave Types (5)
    'hr.leave-types.index',
    'hr.leave-types.show',
    'hr.leave-types.store',
    'hr.leave-types.update',
    'hr.leave-types.destroy',

    // Payroll Periods (5)
    'hr.payroll-periods.index',
    'hr.payroll-periods.show',
    'hr.payroll-periods.store',
    'hr.payroll-periods.update',
    'hr.payroll-periods.destroy',

    // Payroll Items (5)
    'hr.payroll-items.index',
    'hr.payroll-items.show',
    'hr.payroll-items.store',
    'hr.payroll-items.update',
    'hr.payroll-items.destroy',

    // Performance Reviews (5)
    'hr.performance-reviews.index',
    'hr.performance-reviews.show',
    'hr.performance-reviews.store',
    'hr.performance-reviews.update',
    'hr.performance-reviews.destroy',
];

// Create all permissions
foreach ($permissions as $permission) {
    Permission::firstOrCreate([
        'name' => $permission,
        'guard_name' => 'api',
    ]);
}

// Assign to roles
$god = Role::where('name', 'god')->first();
$admin = Role::where('name', 'admin')->first();
$tech = Role::where('name', 'tech')->first();

// god/admin: all permissions
foreach ($permissions as $permission) {
    $god->givePermissionTo($permission);
    $admin->givePermissionTo($permission);
}

// tech: read-only
$readPermissions = array_filter($permissions, fn($p) => str_contains($p, '.index') || str_contains($p, '.show'));
foreach ($readPermissions as $permission) {
    $tech->givePermissionTo($permission);
}
```

#### 0.3 Create Seeders
- LeaveTypeSeeder: Vacation, Sick, Personal, Maternity/Paternity
- DepartmentSeeder: Sample departments (IT, HR, Sales, etc.)

---

### ✅ Phase 1: Core Entities (Day 1, 8 hours)

**Entities:** Department, Position, Employee

#### 1.1 Migrations (1 hour)
- Create 3 migrations with COMPLETE schema
- All indexes from the start
- All foreign keys with proper constraints

#### 1.2 Models (1 hour)
- Department model with scopes (active, withManager)
- Position model with scopes (active, byDepartment)
- Employee model with scopes (active, terminated, byDepartment)
- ALL relationships defined

#### 1.3 Factories (1 hour)
- DepartmentFactory with states: active(), inactive(), withParent()
- PositionFactory with states: active(), inactive(), entry(), senior()
- EmployeeFactory with states: active(), terminated(), onLeave(), manager()

#### 1.4 Complete JSON:API Stack (3 hours)

**For EACH entity (Department, Position, Employee):**

**Controller** (using Actions traits):
```php
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class EmployeeController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
}
```

**Schema** (complete with all fields):
```php
public function fields(): array
{
    return [
        ID::make(),
        Str::make('employeeNumber', 'employee_number')->sortable(),
        Str::make('firstName', 'first_name')->sortable(),
        Str::make('lastName', 'last_name')->sortable(),
        Str::make('email')->sortable(),
        Str::make('phone'),
        Date::make('dateOfBirth', 'date_of_birth'),
        Str::make('gender'),
        Str::make('address'),
        Str::make('city'),
        Str::make('state'),
        Str::make('postalCode', 'postal_code'),
        Str::make('country'),
        Date::make('hireDate', 'hire_date')->sortable(),
        Date::make('terminationDate', 'termination_date'),
        Str::make('employmentStatus', 'employment_status')->sortable(),
        Str::make('employmentType', 'employment_type'),
        Number::make('salary')->sortable(),
        Str::make('salaryCurrency', 'salary_currency'),
        Str::make('paymentFrequency', 'payment_frequency'),
        Str::make('bankAccountNumber', 'bank_account_number'),
        Str::make('bankName', 'bank_name'),
        Str::make('rfc'),
        Str::make('curp'),
        Str::make('nss'),
        Str::make('emergencyContactName', 'emergency_contact_name'),
        Str::make('emergencyContactPhone', 'emergency_contact_phone'),
        Str::make('notes'),
        ArrayHash::make('metadata'),
        BelongsTo::make('user')->type('users')->readOnly(),
        BelongsTo::make('department')->type('hr-departments'),
        BelongsTo::make('position')->type('hr-positions'),
        BelongsTo::make('manager')->type('hr-employees'),
        HasMany::make('subordinates')->type('hr-employees'),
        HasMany::make('attendances')->type('hr-attendances'),
        HasMany::make('leaves')->type('hr-leaves'),
        HasMany::make('payrollItems', 'payrollItems')->type('hr-payroll-items'),
        HasMany::make('performanceReviews', 'performanceReviews')->type('hr-performance-reviews'),
        DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
        DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
    ];
}

public function filters(): array
{
    return [
        WhereIdIn::make($this),
        Where::make('employeeNumber', 'employee_number'),
        Where::make('departmentId', 'department_id'),
        Where::make('positionId', 'position_id'),
        Where::make('managerId', 'manager_id'),
        Where::make('employmentStatus', 'employment_status'),
        Where::make('employmentType', 'employment_type'),
    ];
}

public function pagination(): ?Paginator
{
    return PagePagination::make();
}
```

**Authorizer** (COMPLETE with 10 methods):
```php
class EmployeeAuthorizer implements Authorizer
{
    // 5 CRUD methods
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) return false;

        // Admin/HR can view all
        if ($user->hasAnyRole(['god', 'admin'])) return true;

        // Employees can view themselves
        if ($model->user_id === $user->id) return true;

        // Managers can view their subordinates
        if ($model->manager_id && $model->manager->user_id === $user->id) return true;

        return false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.employees.destroy') ?? false;
    }

    // 5 Relationship methods (CRITICAL - don't forget these!)
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}
```

**Request** (Spanish validation):
```php
public function rules(): array
{
    return [
        'employeeNumber' => ['required', 'string', 'max:50', 'unique:hr_employees,employee_number'],
        'firstName' => ['required', 'string', 'max:255'],
        'lastName' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:hr_employees,email'],
        'phone' => ['sometimes', 'string', 'max:50'],
        'dateOfBirth' => ['sometimes', 'date'],
        'hireDate' => ['required', 'date'],
        'salary' => ['required', 'numeric', 'min:0'],
        'employmentStatus' => ['sometimes', Rule::in(['active', 'on_leave', 'terminated'])],
        'rfc' => ['sometimes', 'string', 'size:13'],
        'department' => JsonApiRule::toOne(),
        'position' => JsonApiRule::toOne(),
        'manager' => JsonApiRule::toOne(),
    ];
}

public function messages(): array
{
    return [
        'employeeNumber.required' => 'El número de empleado es obligatorio.',
        'employeeNumber.unique' => 'El número de empleado ya existe.',
        'firstName.required' => 'El nombre es obligatorio.',
        'lastName.required' => 'El apellido es obligatorio.',
        'email.required' => 'El correo electrónico es obligatorio.',
        'email.email' => 'El correo electrónico debe ser válido.',
        'email.unique' => 'El correo electrónico ya está registrado.',
        'hireDate.required' => 'La fecha de contratación es obligatoria.',
        'salary.required' => 'El salario es obligatorio.',
        'salary.numeric' => 'El salario debe ser un número.',
        'salary.min' => 'El salario no puede ser negativo.',
        'rfc.size' => 'El RFC debe tener 13 caracteres.',
    ];
}
```

**Resource** (complete attributes):
```php
public function attributes($request): iterable
{
    return [
        'employeeNumber' => $this->employee_number,
        'userId' => $this->user_id,
        'firstName' => $this->first_name,
        'lastName' => $this->last_name,
        'fullName' => $this->full_name, // accessor
        'email' => $this->email,
        'phone' => $this->phone,
        'dateOfBirth' => $this->date_of_birth,
        'gender' => $this->gender,
        'address' => $this->address,
        'city' => $this->city,
        'state' => $this->state,
        'postalCode' => $this->postal_code,
        'country' => $this->country,
        'departmentId' => $this->department_id,
        'positionId' => $this->position_id,
        'managerId' => $this->manager_id,
        'hireDate' => $this->hire_date,
        'terminationDate' => $this->termination_date,
        'employmentStatus' => $this->employment_status,
        'employmentType' => $this->employment_type,
        'salary' => $this->salary,
        'salaryCurrency' => $this->salary_currency,
        'paymentFrequency' => $this->payment_frequency,
        'bankAccountNumber' => $this->bank_account_number,
        'bankName' => $this->bank_name,
        'rfc' => $this->rfc,
        'curp' => $this->curp,
        'nss' => $this->nss,
        'emergencyContactName' => $this->emergency_contact_name,
        'emergencyContactPhone' => $this->emergency_contact_phone,
        'notes' => $this->notes,
        'metadata' => $this->metadata,
        'createdAt' => $this->created_at,
        'updatedAt' => $this->updated_at,
    ];
}

public function relationships($request): iterable
{
    return [
        $this->relation('user'),
        $this->relation('department'),
        $this->relation('position'),
        $this->relation('manager'),
        $this->relation('subordinates'),
        $this->relation('attendances'),
        $this->relation('leaves'),
        $this->relation('payrollItems'),
        $this->relation('performanceReviews'),
    ];
}
```

#### 1.5 Register Routes (30 min)
```php
// routes/jsonapi.php
JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:sanctum')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('hr-employees', EmployeeController::class);
        $server->resource('hr-departments', DepartmentController::class);
        $server->resource('hr-positions', PositionController::class);
    });
```

#### 1.6 Register in Server.php (15 min)
```php
// Add imports
use Modules\HR\JsonApi\V1\Employees\EmployeeSchema;
use Modules\HR\JsonApi\V1\Departments\DepartmentSchema;
use Modules\HR\JsonApi\V1\Positions\PositionSchema;

// In allSchemas()
EmployeeSchema::class,
DepartmentSchema::class,
PositionSchema::class,
```

#### 1.7 Tests (1.5 hours)
- Create 15 test files (5 per entity)
- NO RefreshDatabase trait
- Test all CRUD operations
- Test authorization
- Test relationships

---

### ✅ Phase 2: Attendance & Leaves (Day 2, 7 hours)

**Entities:** Attendance, LeaveType, Leave

#### 2.1 Migrations (1 hour)
#### 2.2 Models + Services (1.5 hours)
- AttendanceService with auto-calculation
- LeaveService with approval workflow
#### 2.3 Complete JSON:API Stack (3 hours)
- 3 complete stacks (Attendance, LeaveType, Leave)
- ALL with 10-method Authorizers
#### 2.4 Routes & Registration (30 min)
#### 2.5 Tests (1 hour)
- 15 test files (5 per entity)

---

### ✅ Phase 3: Payroll System (Day 3-4, 12 hours)

**Entities:** PayrollPeriod, PayrollItem

#### 3.1 Migrations (1 hour)
#### 3.2 Models + PayrollService (3 hours)
- Complex payroll calculation logic
- ISR (Mexican income tax) calculation
- IMSS (social security) calculation
- GL posting integration with Accounting module
#### 3.3 Complete JSON:API Stack (4 hours)
- 2 complete stacks
- Complex authorization for payroll
#### 3.4 Accounting Integration (2 hours)
- Journal Entry creation
- Account mapping (Payroll Expense, Payroll Payable, Taxes Payable)
#### 3.5 Routes & Registration (30 min)
#### 3.6 Tests (1.5 hours)
- 10 test files (5 per entity)
- Test payroll calculation
- Test GL posting

---

### ✅ Phase 4: Performance Reviews (Day 5, 4 hours)

**Entity:** PerformanceReview

#### 4.1 Migration (30 min)
#### 4.2 Model (30 min)
#### 4.3 Complete JSON:API Stack (2 hours)
#### 4.4 Routes & Registration (15 min)
#### 4.5 Tests (45 min)
- 5 test files

---

## 🎯 SUCCESS CRITERIA

### Functional
- [ ] 9 entities with full CRUD
- [ ] 45 API endpoints working
- [ ] Payroll calculation accurate
- [ ] GL posting to Accounting
- [ ] Leave approval workflow
- [ ] Attendance auto-calculation

### Technical
- [ ] ALL Controllers use Actions traits
- [ ] ALL Schemas have fields, filters, pagination
- [ ] ALL Authorizers have 10 methods
- [ ] ALL Tests without RefreshDatabase
- [ ] ALL Validation in Spanish
- [ ] 45 permissions created upfront
- [ ] 45+ tests passing (100%)

### Quality
- [ ] JSON:API 1.1 compliant
- [ ] No N+1 queries
- [ ] Proper error handling
- [ ] Spanish validation messages
- [ ] Complete documentation

---

## 📊 METRICS

| Metric | Target |
|--------|--------|
| **Entities** | 9 |
| **Endpoints** | 45 |
| **Permissions** | 45 |
| **Test Files** | 45 |
| **Test Coverage** | >90% |
| **Files Created** | 90+ |
| **Duration** | 4-5 days |

---

## ✅ CHECKLIST PER ENTITY

For EACH entity, ensure:

- [ ] Migration with ALL fields from start
- [ ] Model with scopes and relationships
- [ ] Factory with 3+ state methods
- [ ] Controller with Actions traits ONLY
- [ ] Schema with fields, filters, pagination
- [ ] Authorizer with ALL 10 methods
- [ ] Request with Spanish validation
- [ ] Resource with all attributes + relationships
- [ ] Routes registered in jsonapi.php
- [ ] Schema registered in Server.php
- [ ] 5 test files (Index, Show, Store, Update, Destroy)
- [ ] Tests WITHOUT RefreshDatabase

---

**Document Status:** Planning Complete v2.0
**Last Updated:** 2025-10-31
**Improvements:** Addresses all Phase 4 issues, complete JSON:API compliance
**Next Action:** Review and approve before starting implementation
