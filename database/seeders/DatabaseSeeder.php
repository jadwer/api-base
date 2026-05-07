<?php

namespace Database\Seeders;

use Modules\User\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $system = User::firstOrCreate(
            ['email' => 'system@audit.local'],
            [
                'name' => 'System',
                'password' => 'system',
                'status' => 'active',
            ]
        );

        if ($system->wasRecentlyCreated) {
            activity()->causedBy($system)->log('System user created');
        }

        $this->call([
            // Phase 1: Core permissions and roles (must run first)
            \Modules\PermissionManager\Database\Seeders\PermissionManagerDatabaseSeeder::class,
            \Modules\Audit\Database\Seeders\AuditDatabaseSeeder::class,
            \Modules\PageBuilder\Database\Seeders\PageBuilderDatabaseSeeder::class,

            // Phase 2: Module permissions (before users, so roles get all permissions)
            \Modules\Product\Database\Seeders\ProductDatabaseSeeder::class,
            \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,
            \Modules\Purchase\Database\Seeders\PurchaseDatabaseSeeder::class,
            \Modules\Sales\Database\Seeders\SalesDatabaseSeeder::class,
            \Modules\Ecommerce\Database\Seeders\EcommerceDatabaseSeeder::class,
            \Modules\Contacts\Database\Seeders\ContactsDatabaseSeeder::class,
            \Modules\Accounting\Database\Seeders\AccountingDatabaseSeeder::class,
            \Modules\Finance\Database\Seeders\FinanceDatabaseSeeder::class,
            \Modules\HR\Database\Seeders\HRDatabaseSeeder::class,
            \Modules\Billing\Database\Seeders\BillingDatabaseSeeder::class,
            \Modules\CRM\Database\Seeders\CRMDatabaseSeeder::class,
            \Modules\Reports\Database\Seeders\ReportsDatabaseSeeder::class,
            \Modules\SystemHealth\Database\Seeders\SystemHealthDatabaseSeeder::class,

            // Phase 3: Users (after all permissions exist and are assigned to roles)
            \Modules\User\Database\Seeders\UserDatabaseSeeder::class,

            // Phase 4: AppSettings (placeholder values; tenants override via firstOrCreate
            // in their own seeder under clients/<name>/api/Database/Seeders/).
            \Modules\AppConfig\Database\Seeders\AppSettingSeeder::class,
        ]);
    }
}
