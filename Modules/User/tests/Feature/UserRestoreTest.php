<?php

namespace Modules\User\Tests\Feature;

use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'users.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'users.index', 'guard_name' => 'api']);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(['users.store', 'users.index']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_restore_soft_deleted_user(): void
    {
        $user = User::factory()->create(['name' => 'Deleted User']);
        $userId = $user->id;
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/users/{$userId}/restore");

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Usuario restaurado exitosamente',
            ])
            ->assertJsonPath('data.name', 'Deleted User');

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'deleted_at' => null,
        ]);
    }

    public function test_guest_cannot_restore_user(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->postJson("/api/v1/users/{$user->id}/restore");

        $response->assertUnauthorized();
    }

    public function test_returns_404_for_nonexistent_user(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/users/99999/restore');

        $response->assertNotFound();
    }

    public function test_returns_404_for_non_trashed_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/users/{$user->id}/restore");

        // onlyTrashed() won't find a non-deleted user
        $response->assertNotFound();
    }

    public function test_restored_user_has_null_deleted_at(): void
    {
        $user = User::factory()->create();
        $user->delete();
        $userId = $user->id;

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/users/{$userId}/restore");

        $restored = User::find($userId);
        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at);
    }

    public function test_customer_cannot_restore_user(): void
    {
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $user = User::factory()->create();
        $user->delete();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/users/{$user->id}/restore");

        $response->assertForbidden();
    }
}
