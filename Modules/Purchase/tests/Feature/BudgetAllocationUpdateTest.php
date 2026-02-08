<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\Budget;
use Modules\Purchase\Models\BudgetAllocation;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetAllocationUpdateTest extends TestCase
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

    public function test_admin_can_update_budget_allocation(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create([
            'allocated_amount' => 10000.00,
            'status' => BudgetAllocation::STATUS_COMMITTED,
        ]);

        $data = [
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
            'attributes' => [
                'allocatedAmount' => 20000.00,
                'status' => BudgetAllocation::STATUS_SPENT,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->patch("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertOk();
        $this->assertDatabaseHas('budget_allocations', [
            'id' => $allocation->id,
            'allocated_amount' => 20000.00,
            'status' => 'spent',
        ]);
    }

    public function test_admin_can_update_allocation_status(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->committed()->create();

        $data = [
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
            'attributes' => [
                'status' => BudgetAllocation::STATUS_RELEASED,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->patch("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertOk();
        $this->assertDatabaseHas('budget_allocations', [
            'id' => $allocation->id,
            'status' => 'released',
        ]);
    }

    public function test_admin_can_update_allocation_notes(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create(['notes' => 'Original notes']);

        $data = [
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
            'attributes' => [
                'notes' => 'Updated notes with more detail',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->patch("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertOk();
        $this->assertDatabaseHas('budget_allocations', [
            'id' => $allocation->id,
            'notes' => 'Updated notes with more detail',
        ]);
    }

    public function test_admin_can_update_allocation_amount(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create(['allocated_amount' => 5000.00]);

        $data = [
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
            'attributes' => [
                'allocatedAmount' => 7500.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->patch("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertOk();
        $this->assertDatabaseHas('budget_allocations', [
            'id' => $allocation->id,
            'allocated_amount' => 7500.00,
        ]);
    }

    public function test_cannot_update_allocation_with_invalid_status(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
            'attributes' => [
                'status' => 'invalid_status',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->patch("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertStatus(422);
    }

    public function test_cannot_update_allocation_with_negative_amount(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
            'attributes' => [
                'allocatedAmount' => -100.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->patch("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_update_budget_allocation(): void
    {
        $allocation = BudgetAllocation::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
            'attributes' => [
                'allocatedAmount' => 20000.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->patch("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_update_budget_allocation(): void
    {
        $user = $this->createUserWithPermissions(['budget-allocations.index', 'budget-allocations.show']);
        $allocation = BudgetAllocation::factory()->create();

        $data = [
            'type' => 'budget-allocations',
            'id' => (string) $allocation->id,
            'attributes' => [
                'allocatedAmount' => 20000.00,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('budget-allocations')
            ->withData($data)
            ->patch("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertStatus(403);
    }
}
