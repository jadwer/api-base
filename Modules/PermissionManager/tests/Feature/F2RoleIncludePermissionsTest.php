<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class F2RoleIncludePermissionsTest extends TestCase
{
    public function test_god_can_include_permissions_in_role_index(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $permissions = Permission::query()->take(2)->get();

        $role = Role::factory()->create();
        $role->syncPermissions($permissions);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/vnd.api+json'])
            ->get('/api/v1/roles?include=permissions');

        $response->assertOk();

        foreach ($permissions as $permission) {
            $found = collect($response->json('included'))->contains(function ($included) use ($permission) {
                return $included['type'] === 'permissions'
                    && $included['id'] === (string) $permission->getKey()
                    && $included['attributes']['name'] === $permission->name
                    && $included['attributes']['guard_name'] === $permission->guard_name;
            });

            $this->assertTrue($found, "Permission {$permission->name} was not found in the included section.");
        }
    }

    public function test_god_can_include_permissions_in_role_show(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $permissions = Permission::query()->take(2)->get();

        $role = Role::factory()->create();
        $role->syncPermissions($permissions);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/vnd.api+json'])
            ->get("/api/v1/roles/{$role->getKey()}?include=permissions");

        $response->assertOk();

        foreach ($permissions as $permission) {
            $found = collect($response->json('included'))->contains(function ($included) use ($permission) {
                return $included['type'] === 'permissions'
                    && $included['id'] === (string) $permission->getKey()
                    && $included['attributes']['name'] === $permission->name
                    && $included['attributes']['guard_name'] === $permission->guard_name;
            });

            $this->assertTrue($found, "Permission {$permission->name} was not found in the included section.");
        }
    }

    public function test_user_without_permission_cannot_include_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/vnd.api+json'])
            ->get("/api/v1/roles/{$role->getKey()}?include=permissions");

        $response->assertForbidden();
    }
}
