<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Modules\PermissionManager\Models\Role;
use Modules\PermissionManager\Models\Permission;
use Modules\User\Models\User;

/**
 * CleanRolesAndPermissionsSeeder
 *
 * Creates only the essential roles, permissions, and assigns them.
 * Calls existing module permission seeders to get all permissions.
 */
class CleanRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Step 1: Create System user (required for activity logging)
        $system = User::firstOrCreate(
            ['email' => 'system@audit.local'],
            [
                'name' => 'System',
                'password' => bcrypt('system-' . uniqid()),
                'status' => 'active',
            ]
        );

        // Step 2: Create core roles
        $this->createRoles($system);

        // Step 3: Create all permissions from module seeders
        $this->createAllPermissions();

        // Step 4: Assign permissions to roles
        $this->assignPermissionsToRoles($system);

        $this->command->info('  - Roles and permissions created');
    }

    /**
     * Create the 5 core roles
     */
    private function createRoles(User $system): void
    {
        $roles = [
            ['name' => 'god', 'description' => 'Superadmin del sistema con acceso completo'],
            ['name' => 'admin', 'description' => 'Administrador general de la empresa'],
            ['name' => 'tech', 'description' => 'Usuario técnico de soporte'],
            ['name' => 'customer', 'description' => 'Cliente registrado'],
            ['name' => 'guest', 'description' => 'Invitado sin login'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => 'api'],
                ['description' => $role['description']]
            );
        }

        activity()
            ->causedBy($system)
            ->event('seeding')
            ->log('Core roles created: god, admin, tech, customer, guest');
    }

    /**
     * Call all module permission seeders
     */
    private function createAllPermissions(): void
    {
        // Call all module permission seeders in order
        $this->callSilent([
            // Core permissions
            \Modules\PermissionManager\Database\Seeders\PermissionSeeder::class,

            // Module permissions
            \Modules\Product\Database\Seeders\ProductPermissionSeeder::class,
            \Modules\Inventory\Database\Seeders\InventoryPermissionSeeder::class,
            \Modules\Purchase\Database\Seeders\PurchasePermissionSeeder::class,
            \Modules\Purchase\Database\Seeders\PurchaseOrderItemPermissionSeeder::class,
            \Modules\Sales\Database\Seeders\SalesPermissionSeeder::class,
            \Modules\Ecommerce\Database\Seeders\PermissionsSeeder::class,
            \Modules\Audit\Database\Seeders\AuditPermissionSeeder::class,
            \Modules\PageBuilder\Database\Seeders\PagePermissionSeeder::class,
            \Modules\Contacts\Database\Seeders\PermissionsSeeder::class,
            \Modules\CRM\Database\Seeders\PermissionsSeeder::class,
            \Modules\HR\Database\Seeders\PermissionsSeeder::class,
            \Modules\Accounting\Database\Seeders\PermissionsSeeder::class,
            \Modules\Finance\Database\Seeders\PermissionsSeeder::class,
            \Modules\Billing\Database\Seeders\PermissionsSeeder::class,
            \Modules\SystemHealth\Database\Seeders\SystemHealthPermissionsSeeder::class,
        ]);
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles(User $system): void
    {
        // God gets ALL permissions
        $godRole = Role::findByName('god', 'api');
        $allPermissions = Permission::all();
        $godRole->syncPermissions($allPermissions);

        // Admin gets most permissions except god-only ones
        $adminRole = Role::findByName('admin', 'api');
        $adminPermissions = Permission::whereNotIn('name', [
            // Exclude god-only permissions
            'system-health.purge-logs',
            'permissions.destroy',
            'roles.destroy',
        ])->get();
        $adminRole->syncPermissions($adminPermissions);

        // Tech gets view/read permissions and some operational ones
        $techRole = Role::findByName('tech', 'api');
        $techPermissions = Permission::where(function($query) {
            $query->where('name', 'like', '%.index')
                  ->orWhere('name', 'like', '%.show')
                  ->orWhere('name', 'like', 'profile.%');
        })->get();
        $techRole->syncPermissions($techPermissions);

        // Customer gets limited permissions
        $customerRole = Role::findByName('customer', 'api');
        $customerPermissions = Permission::whereIn('name', [
            'profile.show', 'profile.update',
            'ecommerce.shopping-carts.index', 'ecommerce.shopping-carts.show',
            'ecommerce.shopping-carts.store', 'ecommerce.shopping-carts.update',
            'ecommerce.cart-items.index', 'ecommerce.cart-items.show',
            'ecommerce.cart-items.store', 'ecommerce.cart-items.update',
            'ecommerce.cart-items.destroy',
            'ecommerce.wishlists.index', 'ecommerce.wishlists.show',
            'ecommerce.wishlists.store', 'ecommerce.wishlists.update',
            'ecommerce.wishlist-items.index', 'ecommerce.wishlist-items.show',
            'ecommerce.wishlist-items.store', 'ecommerce.wishlist-items.destroy',
            'ecommerce.product-reviews.index', 'ecommerce.product-reviews.show',
            'ecommerce.product-reviews.store',
            'quotes.index', 'quotes.show',
            'products.index', 'products.show',
            'categories.index', 'categories.show',
            'brands.index', 'brands.show',
        ])->get();
        $customerRole->syncPermissions($customerPermissions);

        // Guest gets no permissions (empty)
        $guestRole = Role::findByName('guest', 'api');
        $guestRole->syncPermissions([]);

        activity()
            ->causedBy($system)
            ->event('seeding')
            ->log('Permissions assigned to all roles');
    }
}
