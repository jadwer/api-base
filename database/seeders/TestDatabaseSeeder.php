<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Optimized seeder for tests
 * Seeds all required modules in a single transaction-aware call
 */
class TestDatabaseSeeder extends Seeder
{
    /**
     * Modules required for tests, in dependency order
     */
    protected array $requiredModules = [
        'PermissionManager',
        'User',
        'Accounting',
        'Finance',
        'Contacts',
        'Product',
        'Inventory',
        'Purchase',
        'Sales',
        'Ecommerce',
        'HR',
        'Billing',
        'CRM',
        'Reports',
        'Audit',
        'SystemHealth',
    ];

    public function run(): void
    {
        foreach ($this->requiredModules as $module) {
            Artisan::call('module:seed', ['module' => $module, '--quiet' => true]);
        }
    }
}
