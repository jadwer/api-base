<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\Budget;
use Modules\Purchase\Models\BudgetAllocation;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetAllocationIndexTest extends TestCase
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

    public function test_admin_can_list_budget_allocations(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        BudgetAllocation::factory()->count(3)->create();

        $response = $this->jsonApi()->get('/api/v1/budget-allocations');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'budgetId',
                        'purchaseOrderId',
                        'allocatedAmount',
                        'status',
                        'notes',
                    ],
                ],
            ],
            'jsonapi',
        ]);
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_allocations_by_budget_id(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();
        BudgetAllocation::factory()->count(2)->create(['budget_id' => $budget->id]);
        BudgetAllocation::factory()->count(1)->create();

        $response = $this->jsonApi()->get("/api/v1/budget-allocations?filter[budgetId]={$budget->id}");

        $response->assertOk();
        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_admin_can_filter_allocations_by_purchase_order_id(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $purchaseOrder = PurchaseOrder::factory()->create();
        BudgetAllocation::factory()->count(2)->create(['purchase_order_id' => $purchaseOrder->id]);
        BudgetAllocation::factory()->count(1)->create();

        $response = $this->jsonApi()->get("/api/v1/budget-allocations?filter[purchaseOrderId]={$purchaseOrder->id}");

        $response->assertOk();
        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_admin_can_filter_allocations_by_status(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        BudgetAllocation::factory()->count(2)->committed()->create();
        BudgetAllocation::factory()->count(1)->spent()->create();

        $response = $this->jsonApi()->get('/api/v1/budget-allocations?filter[status]=committed');

        $response->assertOk();
        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_admin_can_sort_allocations_by_allocated_amount(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        BudgetAllocation::factory()->create(['allocated_amount' => 50000.00]);
        BudgetAllocation::factory()->create(['allocated_amount' => 10000.00]);

        $response = $this->jsonApi()->get('/api/v1/budget-allocations?sort=allocatedAmount');

        $response->assertOk();
        $amounts = collect($response->json('data'))->pluck('attributes.allocatedAmount');
        $this->assertEquals(10000, $amounts->first());
    }

    public function test_admin_can_sort_allocations_by_status(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        BudgetAllocation::factory()->spent()->create();
        BudgetAllocation::factory()->committed()->create();

        $response = $this->jsonApi()->get('/api/v1/budget-allocations?sort=status');

        $response->assertOk();
        $statuses = collect($response->json('data'))->pluck('attributes.status');
        $this->assertEquals('committed', $statuses->first());
    }

    public function test_admin_can_include_budget_relationship(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        BudgetAllocation::factory()->create();

        $response = $this->jsonApi()->get('/api/v1/budget-allocations?include=budget');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'budget',
                    ],
                ],
            ],
            'included',
        ]);
    }

    public function test_admin_can_include_purchase_order_relationship(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        BudgetAllocation::factory()->create();

        $response = $this->jsonApi()->get('/api/v1/budget-allocations?include=purchaseOrder');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'purchaseOrder',
                    ],
                ],
            ],
            'included',
        ]);
    }

    public function test_unauthorized_user_cannot_list_budget_allocations(): void
    {
        $response = $this->jsonApi()->get('/api/v1/budget-allocations');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_budget_allocations(): void
    {
        $user = $this->createUserWithPermissions([]);

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/budget-allocations');
        $response->assertStatus(403);
    }
}
