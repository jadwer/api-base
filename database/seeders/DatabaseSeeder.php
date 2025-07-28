<?php

namespace Database\Seeders;

use App\Models\User;
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

        $system = User::factory()->create([
                'name' => 'System',
                'email' => 'system@audit.local',
                'password' => 'system',
                'status' => 'active',
        ]);

        activity()->causedBy($system)->log('System user created');

        $this->call([
            \Modules\PermissionManager\Database\Seeders\PermissionManagerDatabaseSeeder::class,
            \Modules\Audit\Database\Seeders\AuditDatabaseSeeder::class,
            \Modules\PageBuilder\Database\Seeders\PageBuilderDatabaseSeeder::class,
            \Modules\User\Database\Seeders\UserDatabaseSeeder::class,
            \Modules\Product\Database\Seeders\ProductDatabaseSeeder::class,
            \Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder::class,
            \Modules\Purchase\Database\Seeders\PurchaseDatabaseSeeder::class,
        ]);
    }
}
