<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetUpdateTest extends TestCase
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

    public function test_admin_can_update_budget(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create([
            'name' => 'Original Name',
            'budgeted_amount' => 100000.00,
        ]);

        $data = [
            'type' => 'budgets',
            'id' => (string) $budget->id,
            'attributes' => [
                'name' => 'Updated Name',
                'budgetedAmount' => 150000.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->patch("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'name' => 'Updated Name',
            'budgeted_amount' => 150000.00,
        ]);
    }

    public function test_admin_can_update_budget_thresholds(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create([
            'warning_threshold' => 80,
            'critical_threshold' => 95,
        ]);

        $data = [
            'type' => 'budgets',
            'id' => (string) $budget->id,
            'attributes' => [
                'warningThreshold' => 70,
                'criticalThreshold' => 90,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->patch("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'warning_threshold' => 70,
            'critical_threshold' => 90,
        ]);
    }

    public function test_admin_can_deactivate_budget(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create(['is_active' => true]);

        $data = [
            'type' => 'budgets',
            'id' => (string) $budget->id,
            'attributes' => [
                'isActive' => false,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->patch("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'is_active' => false,
        ]);
    }

    public function test_cannot_update_budget_with_duplicate_code(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        Budget::factory()->create(['code' => 'EXISTING-CODE']);
        $budget = Budget::factory()->create(['code' => 'MY-CODE']);

        $data = [
            'type' => 'budgets',
            'id' => (string) $budget->id,
            'attributes' => [
                'code' => 'EXISTING-CODE',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->patch("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(422);
    }

    public function test_can_update_budget_with_same_code(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create(['code' => 'MY-CODE']);

        $data = [
            'type' => 'budgets',
            'id' => (string) $budget->id,
            'attributes' => [
                'code' => 'MY-CODE',
                'name' => 'Updated Name',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->patch("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
    }

    public function test_unauthorized_user_cannot_update_budget(): void
    {
        $budget = Budget::factory()->create();

        $data = [
            'type' => 'budgets',
            'id' => (string) $budget->id,
            'attributes' => [
                'name' => 'Unauthorized Update',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->patch("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_update_budget(): void
    {
        $user = $this->createUserWithPermissions(['budgets.index', 'budgets.show']);
        $budget = Budget::factory()->create();

        $data = [
            'type' => 'budgets',
            'id' => (string) $budget->id,
            'attributes' => [
                'name' => 'No Permission Update',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->patch("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(403);
    }
}
