<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_user_permissions(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        
        // Check if user has admin role
        $this->assertTrue($admin->hasRole('admin'), 'User should have admin role');
        
        // Check both permissions - products should work, warehouses might not
        $this->assertTrue($admin->can('products.index'), 'User should have products.index permission');
        $this->assertTrue($admin->can('warehouses.index'), 'User should have warehouses.index permission');
        
        $this->actingAs($admin, 'sanctum');
        
        // Make a simple request to see the response
        $response = $this->jsonApi()->get('/api/v1/warehouses');
        
        // Let's see what we get
        dump($response->getStatusCode());
        dump($response->json());
        
        $response->assertOk();
    }
}
