<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Purchase\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierBasicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_has_suppliers_permissions(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        
        $this->assertTrue($admin->can('suppliers.index'));
        $this->assertTrue($admin->can('suppliers.show'));
        $this->assertTrue($admin->can('suppliers.store'));
    }

    public function test_simple_api_call_with_admin(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Create test data
        Supplier::factory()->create();

        $response = $this->jsonApi()->get('/api/v1/suppliers');

        $response->assertOk();
    }
}
