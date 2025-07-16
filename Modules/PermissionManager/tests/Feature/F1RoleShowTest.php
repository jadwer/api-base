<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class F1RoleShowTest extends TestCase
{
    public function test_god_can_view_role(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $role = Role::first();

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->get("/api/v1/roles/{$role->getRouteKey()}");

        $response->assertSuccessful();
        $response->assertFetchedOne([
            'type' => 'roles',
            'id' => (string) $role->id,
            'attributes' => [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ],
        ]);
    }

    public function test_user_without_permission_cannot_view_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->jsonApi()->get("/api/v1/roles/{$role->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_role(): void
    {
        $role = Role::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/roles/{$role->id}");

        $response->assertUnauthorized();
    }
}