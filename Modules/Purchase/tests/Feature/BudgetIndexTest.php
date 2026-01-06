<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Purchase\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetIndexTest extends TestCase
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

    public function test_admin_can_list_budgets(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        Budget::factory()->count(3)->create();

        $response = $this->jsonApi()->get('/api/v1/budgets');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'code',
                        'budgetType',
                        'periodType',
                        'budgetedAmount',
                        'committedAmount',
                        'spentAmount',
                        'isActive',
                    ],
                ],
            ],
            'jsonapi',
        ]);
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_budgets_by_type(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        Budget::factory()->count(2)->create(['budget_type' => Budget::TYPE_DEPARTMENT]);
        Budget::factory()->count(1)->create(['budget_type' => Budget::TYPE_PROJECT]);

        $response = $this->jsonApi()->get('/api/v1/budgets?filter[budgetType]=department');

        $response->assertOk();
        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_admin_can_filter_budgets_by_active_status(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        Budget::factory()->count(2)->create(['is_active' => true]);
        Budget::factory()->count(1)->create(['is_active' => false]);

        $response = $this->jsonApi()->get('/api/v1/budgets?filter[isActive]=1');

        $response->assertOk();
        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_admin_can_sort_budgets_by_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        Budget::factory()->create(['name' => 'Zebra Budget']);
        Budget::factory()->create(['name' => 'Alpha Budget']);

        $response = $this->jsonApi()->get('/api/v1/budgets?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertEquals('Alpha Budget', $names->first());
    }

    public function test_unauthorized_user_cannot_list_budgets(): void
    {
        $response = $this->jsonApi()->get('/api/v1/budgets');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_budgets(): void
    {
        $user = $this->createUserWithPermissions([]);

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->get('/api/v1/budgets');
        $response->assertStatus(403);
    }
}
