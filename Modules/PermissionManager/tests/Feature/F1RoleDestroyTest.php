<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class F1RoleDestroyTest extends TestCase
{
    public function test_god_can_delete_role(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $role = Role::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/roles/{$role->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_user_without_permission_cannot_delete_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/roles/{$role->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_delete_role(): void
    {
        $role = Role::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/roles/{$role->id}");

        $response->assertUnauthorized();
    }
}