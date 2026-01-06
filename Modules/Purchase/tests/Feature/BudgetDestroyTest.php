<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\Budget;
use Modules\Purchase\Models\BudgetAllocation;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Contacts\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetDestroyTest extends TestCase
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

    public function test_admin_can_delete_budget_without_allocations(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();

        $response = $this->jsonApi()
            ->delete("/api/v1/budgets/{$budget->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_admin_can_delete_inactive_budget(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create(['is_active' => false]);

        $response = $this->jsonApi()
            ->delete("/api/v1/budgets/{$budget->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_returns_404_for_nonexistent_budget(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()
            ->delete('/api/v1/budgets/99999');

        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_delete_budget(): void
    {
        $budget = Budget::factory()->create();

        $response = $this->jsonApi()
            ->delete("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
    }

    public function test_user_without_permission_cannot_delete_budget(): void
    {
        $user = $this->createUserWithPermissions(['budgets.index', 'budgets.show']);
        $budget = Budget::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
    }

    public function test_deleting_budget_cascades_allocations(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $budget = Budget::factory()->create();
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $purchaseOrder = PurchaseOrder::factory()->create(['contact_id' => $contact->id]);

        // Create allocation
        $allocation = BudgetAllocation::create([
            'budget_id' => $budget->id,
            'purchase_order_id' => $purchaseOrder->id,
            'allocated_amount' => 5000.00,
            'status' => 'committed',
        ]);

        $response = $this->jsonApi()
            ->delete("/api/v1/budgets/{$budget->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
        $this->assertDatabaseMissing('budget_allocations', ['id' => $allocation->id]);
    }
}
