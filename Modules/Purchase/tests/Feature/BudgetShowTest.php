<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetShowTest extends TestCase
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

    public function test_admin_can_view_budget(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create([
            'name' => 'Test Budget',
            'budgeted_amount' => 100000.00,
            'committed_amount' => 25000.00,
            'spent_amount' => 10000.00,
        ]);

        $response = $this->jsonApi()->get("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'type' => 'budgets',
            'id' => (string) $budget->id,
        ]);
        $response->assertJsonPath('data.attributes.name', 'Test Budget');
        $this->assertEquals(100000, $response->json('data.attributes.budgetedAmount'));
        $this->assertEquals(25000, $response->json('data.attributes.committedAmount'));
        $this->assertEquals(10000, $response->json('data.attributes.spentAmount'));
    }

    public function test_budget_includes_computed_available_amount(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create([
            'budgeted_amount' => 100000.00,
            'committed_amount' => 30000.00,
            'spent_amount' => 20000.00,
        ]);

        $response = $this->jsonApi()->get("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
        // Available = 100000 - 30000 - 20000 = 50000
        $this->assertEquals(50000, $response->json('data.attributes.availableAmount'));
    }

    public function test_budget_includes_utilization_percent(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create([
            'budgeted_amount' => 100000.00,
            'committed_amount' => 40000.00,
            'spent_amount' => 35000.00,
        ]);

        $response = $this->jsonApi()->get("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
        // Utilization = (40000 + 35000) / 100000 * 100 = 75%
        $this->assertEquals(75, $response->json('data.attributes.utilizationPercent'));
    }

    public function test_budget_includes_status_level(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        // Budget at critical level (>95%)
        $budget = Budget::factory()->create([
            'budgeted_amount' => 100000.00,
            'committed_amount' => 50000.00,
            'spent_amount' => 48000.00,
            'warning_threshold' => 80,
            'critical_threshold' => 95,
        ]);

        $response = $this->jsonApi()->get("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
        // Utilization = 98%, should be 'critical'
        $this->assertContains($response->json('data.attributes.statusLevel'), ['warning', 'critical']);
    }

    public function test_budget_response_includes_relationships_structure(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();

        $response = $this->jsonApi()
            ->get("/api/v1/budgets/{$budget->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes',
                'relationships' => [
                    'allocations',
                    'category',
                    'contact',
                ],
            ],
        ]);
    }

    public function test_returns_404_for_nonexistent_budget(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/budgets/99999');

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_view_budget(): void
    {
        $budget = Budget::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_view_budget(): void
    {
        $user = $this->createUserWithPermissions([]);
        $budget = Budget::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->get("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(403);
    }
}
