<?php

namespace Modules\HR\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class PermissionsSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding permissions...');

        $permissions = [
            'god',
            'admin',
            'tech',
            'customer',
            'hr.departments.index',
            'hr.departments.show',
            'hr.departments.store',
            'hr.departments.update',
            'hr.departments.destroy',
            'hr.positions.index',
            'hr.positions.show',
            'hr.positions.store',
            'hr.positions.update',
            'hr.positions.destroy',
            'hr.employees.index',
            'hr.employees.show',
            'hr.employees.store',
            'hr.employees.update',
            'hr.employees.destroy',
            'hr.attendances.index',
            'hr.attendances.show',
            'hr.attendances.store',
            'hr.attendances.update',
            'hr.attendances.destroy',
            'hr.leaves.index',
            'hr.leaves.show',
            'hr.leaves.store',
            'hr.leaves.update',
            'hr.leaves.destroy',
            'hr.leave-types.index',
            'hr.leave-types.show',
            'hr.leave-types.store',
            'hr.leave-types.update',
            'hr.leave-types.destroy',
            'hr.payroll-periods.index',
            'hr.payroll-periods.show',
            'hr.payroll-periods.store',
            'hr.payroll-periods.update',
            'hr.payroll-periods.destroy',
            'hr.payroll-items.index',
            'hr.payroll-items.show',
            'hr.payroll-items.store',
            'hr.payroll-items.update',
            'hr.payroll-items.destroy',
            'hr.performance-reviews.index',
            'hr.performance-reviews.show',
            'hr.performance-reviews.store',
            'hr.performance-reviews.update',
            'hr.performance-reviews.destroy',
        ];

        $this->bulkCreatePermissions($permissions);

        $this->command->info('✅ Permissions seeded successfully!');
    }
}
