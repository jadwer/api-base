<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\BudgetAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetAllocationShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\PermissionManager\Database\Seeders\RoleSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Modules\PermissionManager\Database\Seeders\AssignPermissionsSeeder::class);
        $this->seed(\Modules\Purchase\Database\Seeders\PurchasePermissionSeeder::class);

        $this->createAdminUser();
    }

    private function createAdminUser(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador General',
                'password' => 'secureadmin',
                'status' => 'active',
            ]
        );

        if (!$admin->hasRole('admin', 'api')) {
            $admin->assignRole('admin');
        }

        return $admin;
    }

    private function createUserWithPermissions(array $permissions = []): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'test_role_' . uniqid(), 'guard_name' => 'api']);

        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        return $user;
    }

    public function test_admin_can_view_budget_allocation(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create([
            'allocated_amount' => 25000.00,
            'status' => BudgetAllocation::STATUS_COMMITTED,
            'notes' => 'Test allocation notes',
        ]);

        $response = $this->jsonApi()->get("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
        ]);
        $response->assertJsonPath('data.attributes.status', 'committed');
        $this->assertEquals(25000, $response->json('data.attributes.allocatedAmount'));
        $response->assertJsonPath('data.attributes.notes', 'Test allocation notes');
    }

    public function test_allocation_response_includes_relationships_structure(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create();

        $response = $this->jsonApi()
            ->get("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes',
                'relationships' => [
                    'budget',
                    'purchaseOrder',
                ],
            ],
        ]);
    }

    public function test_can_include_budget_relationship(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create();

        $response = $this->jsonApi()
            ->get("/api/v1/budget-allocations/{$allocation->id}?include=budget");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'budget',
                ],
            ],
            'included',
        ]);
    }

    public function test_can_include_purchase_order_relationship(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create();

        $response = $this->jsonApi()
            ->get("/api/v1/budget-allocations/{$allocation->id}?include=purchaseOrder");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'purchaseOrder',
                ],
            ],
            'included',
        ]);
    }

    public function test_returns_404_for_nonexistent_budget_allocation(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/budget-allocations/99999');

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_view_budget_allocation(): void
    {
        $allocation = BudgetAllocation::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_view_budget_allocation(): void
    {
        $user = $this->createUserWithPermissions([]);
        $allocation = BudgetAllocation::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->get("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertStatus(403);
    }
}
