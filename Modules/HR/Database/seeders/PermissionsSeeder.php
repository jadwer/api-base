<?php

namespace Modules\HR\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates all 45 HR module permissions (9 entities × 5 actions)
     * and assigns them to appropriate roles.
     */
    public function run(): void
    {
        // Get or create roles
        $rolegod = Role::firstOrCreate(['name' => 'god', 'guard_name' => 'api']);
        $roleadmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $roletech = Role::firstOrCreate(['name' => 'tech', 'guard_name' => 'api']);
        $rolecustomer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        // =========================================================================
        // PHASE 1: CORE ENTITIES
        // =========================================================================

        // Department Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.departments.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.departments.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.departments.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.departments.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.departments.destroy', 'guard_name' => 'api']);

        // Position Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.positions.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.positions.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.positions.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.positions.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.positions.destroy', 'guard_name' => 'api']);

        // Employee Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.employees.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.employees.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.employees.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.employees.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.employees.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // PHASE 2: ATTENDANCE & LEAVES
        // =========================================================================

        // Attendance Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.attendances.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.attendances.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.attendances.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.attendances.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.attendances.destroy', 'guard_name' => 'api']);

        // Leave Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.leaves.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.leaves.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.leaves.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.leaves.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.leaves.destroy', 'guard_name' => 'api']);

        // LeaveType Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.leave-types.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.leave-types.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.leave-types.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.leave-types.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.leave-types.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // PHASE 3: PAYROLL
        // =========================================================================

        // PayrollPeriod Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.payroll-periods.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.payroll-periods.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.payroll-periods.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.payroll-periods.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.payroll-periods.destroy', 'guard_name' => 'api']);

        // PayrollItem Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.payroll-items.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.payroll-items.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.payroll-items.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.payroll-items.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.payroll-items.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // PHASE 4: PERFORMANCE
        // =========================================================================

        // PerformanceReview Permissions (5)
        Permission::firstOrCreate(['name' => 'hr.performance-reviews.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.performance-reviews.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.performance-reviews.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.performance-reviews.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'hr.performance-reviews.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // ROLE ASSIGNMENTS
        // =========================================================================

        // GOD role - ALL permissions
        $rolegod->givePermissionTo([
            // Departments
            'hr.departments.index', 'hr.departments.show', 'hr.departments.store',
            'hr.departments.update', 'hr.departments.destroy',

            // Positions
            'hr.positions.index', 'hr.positions.show', 'hr.positions.store',
            'hr.positions.update', 'hr.positions.destroy',

            // Employees
            'hr.employees.index', 'hr.employees.show', 'hr.employees.store',
            'hr.employees.update', 'hr.employees.destroy',

            // Attendances
            'hr.attendances.index', 'hr.attendances.show', 'hr.attendances.store',
            'hr.attendances.update', 'hr.attendances.destroy',

            // Leaves
            'hr.leaves.index', 'hr.leaves.show', 'hr.leaves.store',
            'hr.leaves.update', 'hr.leaves.destroy',

            // LeaveTypes
            'hr.leave-types.index', 'hr.leave-types.show', 'hr.leave-types.store',
            'hr.leave-types.update', 'hr.leave-types.destroy',

            // PayrollPeriods
            'hr.payroll-periods.index', 'hr.payroll-periods.show', 'hr.payroll-periods.store',
            'hr.payroll-periods.update', 'hr.payroll-periods.destroy',

            // PayrollItems
            'hr.payroll-items.index', 'hr.payroll-items.show', 'hr.payroll-items.store',
            'hr.payroll-items.update', 'hr.payroll-items.destroy',

            // PerformanceReviews
            'hr.performance-reviews.index', 'hr.performance-reviews.show', 'hr.performance-reviews.store',
            'hr.performance-reviews.update', 'hr.performance-reviews.destroy',
        ]);

        // ADMIN role - Full HR management permissions
        $roleadmin->givePermissionTo([
            // Departments
            'hr.departments.index', 'hr.departments.show', 'hr.departments.store',
            'hr.departments.update', 'hr.departments.destroy',

            // Positions
            'hr.positions.index', 'hr.positions.show', 'hr.positions.store',
            'hr.positions.update', 'hr.positions.destroy',

            // Employees
            'hr.employees.index', 'hr.employees.show', 'hr.employees.store',
            'hr.employees.update', 'hr.employees.destroy',

            // Attendances
            'hr.attendances.index', 'hr.attendances.show', 'hr.attendances.store',
            'hr.attendances.update', 'hr.attendances.destroy',

            // Leaves
            'hr.leaves.index', 'hr.leaves.show', 'hr.leaves.store',
            'hr.leaves.update', 'hr.leaves.destroy',

            // LeaveTypes
            'hr.leave-types.index', 'hr.leave-types.show', 'hr.leave-types.store',
            'hr.leave-types.update', 'hr.leave-types.destroy',

            // PayrollPeriods
            'hr.payroll-periods.index', 'hr.payroll-periods.show', 'hr.payroll-periods.store',
            'hr.payroll-periods.update', 'hr.payroll-periods.destroy',

            // PayrollItems
            'hr.payroll-items.index', 'hr.payroll-items.show', 'hr.payroll-items.store',
            'hr.payroll-items.update', 'hr.payroll-items.destroy',

            // PerformanceReviews
            'hr.performance-reviews.index', 'hr.performance-reviews.show', 'hr.performance-reviews.store',
            'hr.performance-reviews.update', 'hr.performance-reviews.destroy',
        ]);

        // TECH role - Read-only access for reporting and support
        $roletech->givePermissionTo([
            // Departments (read-only)
            'hr.departments.index', 'hr.departments.show',

            // Positions (read-only)
            'hr.positions.index', 'hr.positions.show',

            // Employees (read-only)
            'hr.employees.index', 'hr.employees.show',

            // Attendances (read-only)
            'hr.attendances.index', 'hr.attendances.show',

            // Leaves (read-only)
            'hr.leaves.index', 'hr.leaves.show',

            // LeaveTypes (read-only)
            'hr.leave-types.index', 'hr.leave-types.show',

            // PayrollPeriods (read-only)
            'hr.payroll-periods.index', 'hr.payroll-periods.show',

            // PayrollItems (read-only)
            'hr.payroll-items.index', 'hr.payroll-items.show',

            // PerformanceReviews (read-only)
            'hr.performance-reviews.index', 'hr.performance-reviews.show',
        ]);

        // CUSTOMER role - No HR permissions (internal module)
        // Customers don't have access to HR data

        $this->command->info('✅ HR Permissions created successfully: 45 permissions (9 entities × 5 actions)');
        $this->command->info('✅ God role: 45 permissions');
        $this->command->info('✅ Admin role: 45 permissions');
        $this->command->info('✅ Tech role: 18 permissions (read-only)');
        $this->command->info('✅ Customer role: 0 permissions (internal module)');
    }
}
