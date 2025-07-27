<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_customer_permissions(): void
    {
        // Create customer exactly like in the failing test
        $customer = User::factory()->create(['email' => 'test-customer@example.com']);
        $customerRole = \Modules\PermissionManager\Models\Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'api'
        ]);
        $customer->assignRole($customerRole);
        
        // Check permissions
        $hasRole = $customer->hasRole('customer');
        $hasWarehousePermission = $customer->can('warehouses.index');
        
        dump("Customer has role 'customer': " . ($hasRole ? 'YES' : 'NO'));
        dump("Customer can 'warehouses.index': " . ($hasWarehousePermission ? 'YES' : 'NO'));
        
        // Check what permissions the customer role has
        $rolePermissions = $customerRole->permissions->pluck('name')->toArray();
        dump("Customer role permissions: ", $rolePermissions);
        
        $this->assertFalse($hasWarehousePermission, 'Customer should NOT have warehouses.index permission');
    }
}
