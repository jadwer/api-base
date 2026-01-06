<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetStoreTest extends TestCase
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

    public function test_admin_can_create_budget(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'budgets',
            'attributes' => [
                'name' => 'Q1 2026 Marketing Budget',
                'code' => 'MKT-2026-Q1',
                'description' => 'Marketing department budget for Q1 2026',
                'budgetType' => Budget::TYPE_DEPARTMENT,
                'departmentCode' => 'MKT',
                'periodType' => Budget::PERIOD_QUARTERLY,
                'startDate' => '2026-01-01',
                'endDate' => '2026-03-31',
                'fiscalYear' => 2026,
                'budgetedAmount' => 150000.00,
                'warningThreshold' => 80,
                'criticalThreshold' => 95,
                'hardLimit' => false,
                'allowOvercommit' => true,
                'isActive' => true,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->post('/api/v1/budgets');

        $response->assertCreated();
        $this->assertDatabaseHas('budgets', [
            'name' => 'Q1 2026 Marketing Budget',
            'code' => 'MKT-2026-Q1',
            'budget_type' => Budget::TYPE_DEPARTMENT,
            'budgeted_amount' => 150000.00,
        ]);
    }

    public function test_admin_can_create_budget_with_minimal_data(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'budgets',
            'attributes' => [
                'name' => 'Basic Budget',
                'code' => 'BASIC-001',
                'budgetType' => Budget::TYPE_GENERAL,
                'periodType' => Budget::PERIOD_ANNUAL,
                'startDate' => '2026-01-01',
                'endDate' => '2026-12-31',
                'budgetedAmount' => 50000.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->post('/api/v1/budgets');

        $response->assertCreated();
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'budgets',
            'attributes' => [
                'name' => 'Incomplete Budget',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->post('/api/v1/budgets');

        $response->assertStatus(422);
    }

    public function test_validation_fails_with_duplicate_code(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        Budget::factory()->create(['code' => 'UNIQUE-CODE']);

        $data = [
            'type' => 'budgets',
            'attributes' => [
                'name' => 'Duplicate Budget',
                'code' => 'UNIQUE-CODE',
                'budgetType' => Budget::TYPE_GENERAL,
                'periodType' => Budget::PERIOD_ANNUAL,
                'startDate' => '2026-01-01',
                'endDate' => '2026-12-31',
                'budgetedAmount' => 50000.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->post('/api/v1/budgets');

        $response->assertStatus(422);
    }

    public function test_validation_fails_with_end_date_before_start_date(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'budgets',
            'attributes' => [
                'name' => 'Invalid Dates Budget',
                'code' => 'INVALID-001',
                'budgetType' => Budget::TYPE_GENERAL,
                'periodType' => Budget::PERIOD_ANNUAL,
                'startDate' => '2026-12-31',
                'endDate' => '2026-01-01',
                'budgetedAmount' => 50000.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->post('/api/v1/budgets');

        $response->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_create_budget(): void
    {
        $data = [
            'type' => 'budgets',
            'attributes' => [
                'name' => 'Unauthorized Budget',
                'code' => 'UNAUTH-001',
                'budgetType' => Budget::TYPE_GENERAL,
                'periodType' => Budget::PERIOD_ANNUAL,
                'startDate' => '2026-01-01',
                'endDate' => '2026-12-31',
                'budgetedAmount' => 50000.00,
            ],
        ];

        $response = $this->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->post('/api/v1/budgets');

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_create_budget(): void
    {
        $user = $this->createUserWithPermissions(['budgets.index']);

        $data = [
            'type' => 'budgets',
            'attributes' => [
                'name' => 'No Permission Budget',
                'code' => 'NOPERM-001',
                'budgetType' => Budget::TYPE_GENERAL,
                'periodType' => Budget::PERIOD_ANNUAL,
                'startDate' => '2026-01-01',
                'endDate' => '2026-12-31',
                'budgetedAmount' => 50000.00,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('budgets')
            ->withData($data)
            ->post('/api/v1/budgets');

        $response->assertStatus(403);
    }
}
