<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\BudgetAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetAllocationDestroyTest extends TestCase
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

    public function test_admin_can_delete_budget_allocation(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->create();

        $response = $this->jsonApi()
            ->delete("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('budget_allocations', ['id' => $allocation->id]);
    }

    public function test_admin_can_delete_committed_allocation(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->committed()->create();

        $response = $this->jsonApi()
            ->delete("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('budget_allocations', ['id' => $allocation->id]);
    }

    public function test_admin_can_delete_cancelled_allocation(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $allocation = BudgetAllocation::factory()->cancelled()->create();

        $response = $this->jsonApi()
            ->delete("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('budget_allocations', ['id' => $allocation->id]);
    }

    public function test_returns_404_for_nonexistent_budget_allocation(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()
            ->delete('/api/v1/budget-allocations/99999');

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_delete_budget_allocation(): void
    {
        $allocation = BudgetAllocation::factory()->create();

        $response = $this->jsonApi()
            ->delete("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('budget_allocations', ['id' => $allocation->id]);
    }

    public function test_user_without_permission_cannot_delete_budget_allocation(): void
    {
        $user = $this->createUserWithPermissions(['budget-allocations.index', 'budget-allocations.show']);
        $allocation = BudgetAllocation::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/budget-allocations/{$allocation->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('budget_allocations', ['id' => $allocation->id]);
    }
}
