<?php

namespace Modules\HR\Database\Seeders;

use Illuminate\Database\Seeder;

class HRDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Seeding HR module...');

        $this->call([
            PermissionsSeeder::class,
            HRAssignPermissionsSeeder::class,
            // ✅ DEMO DATA - Enabled for presentation
            HRDemoDataSeeder::class, // Departments, Employees, Payroll, etc.
        ]);

        $this->command->info('🎉 HR module seeded successfully!');
    }
}
