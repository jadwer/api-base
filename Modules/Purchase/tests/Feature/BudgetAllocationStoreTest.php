<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\Budget;
use Modules\Purchase\Models\BudgetAllocation;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetAllocationStoreTest extends TestCase
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

    public function test_admin_can_create_budget_allocation(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'budgetId' => $budget->id,
                'purchaseOrderId' => $purchaseOrder->id,
                'allocatedAmount' => 15000.00,
                'status' => BudgetAllocation::STATUS_COMMITTED,
                'notes' => 'Initial allocation for Q1 supplies',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertCreated();
        $this->assertDatabaseHas('budget_allocations', [
            'budget_id' => $budget->id,
            'purchase_order_id' => $purchaseOrder->id,
            'allocated_amount' => 15000.00,
            'status' => 'committed',
            'notes' => 'Initial allocation for Q1 supplies',
        ]);
    }

    public function test_admin_can_create_allocation_with_minimal_data(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'budgetId' => $budget->id,
                'purchaseOrderId' => $purchaseOrder->id,
                'allocatedAmount' => 5000.00,
                'status' => BudgetAllocation::STATUS_COMMITTED,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertCreated();
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'notes' => 'Missing required fields',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertStatus(422);
    }

    public function test_validation_fails_without_budget_id(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $purchaseOrder = PurchaseOrder::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'purchaseOrderId' => $purchaseOrder->id,
                'allocatedAmount' => 5000.00,
                'status' => BudgetAllocation::STATUS_COMMITTED,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertStatus(422);
    }

    public function test_validation_fails_without_purchase_order_id(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'budgetId' => $budget->id,
                'allocatedAmount' => 5000.00,
                'status' => BudgetAllocation::STATUS_COMMITTED,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertStatus(422);
    }

    public function test_validation_fails_with_invalid_status(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'budgetId' => $budget->id,
                'purchaseOrderId' => $purchaseOrder->id,
                'allocatedAmount' => 5000.00,
                'status' => 'invalid_status',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertStatus(422);
    }

    public function test_validation_fails_with_nonexistent_budget(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $purchaseOrder = PurchaseOrder::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'budgetId' => 99999,
                'purchaseOrderId' => $purchaseOrder->id,
                'allocatedAmount' => 5000.00,
                'status' => BudgetAllocation::STATUS_COMMITTED,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertStatus(422);
    }

    public function test_validation_fails_with_negative_amount(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'budgetId' => $budget->id,
                'purchaseOrderId' => $purchaseOrder->id,
                'allocatedAmount' => -500.00,
                'status' => BudgetAllocation::STATUS_COMMITTED,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_create_budget_allocation(): void
    {
        $budget = Budget::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'budgetId' => $budget->id,
                'purchaseOrderId' => $purchaseOrder->id,
                'allocatedAmount' => 5000.00,
                'status' => BudgetAllocation::STATUS_COMMITTED,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_create_budget_allocation(): void
    {
        $user = $this->createUserWithPermissions(['budget-allocations.index']);

        $budget = Budget::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'attributes' => [
                'budgetId' => $budget->id,
                'purchaseOrderId' => $purchaseOrder->id,
                'allocatedAmount' => 5000.00,
                'status' => BudgetAllocation::STATUS_COMMITTED,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->post('/api/v1/budget-allocations');

        $response->assertStatus(403);
    }
}
