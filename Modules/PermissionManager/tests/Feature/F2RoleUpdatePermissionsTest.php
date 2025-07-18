<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class F2RoleUpdatePermissionsTest extends TestCase
{
    public function test_god_can_update_role_permissions(): void
    {
        $user = User::where('email', 'god@example.com')->first();

        $role = Role::factory()->create([
            'name' => 'updatable.role',
            'guard_name' => 'api',
        ]);

        $initial = Permission::query()->take(2)->get();
        $role->syncPermissions($initial);

        $newPermissions = Permission::query()
            ->whereNotIn('id', $initial->pluck('id'))
            ->take(2)
            ->get();

        $payload = [
            'type' => 'roles',
            'id' => (string) $role->getKey(),
            'attributes' => [
                'name' => 'updatable.role',
                'guard_name' => 'api',
            ],
            'relationships' => [
                'permissions' => [
                    'data' => $newPermissions->map(fn($p) => [
                        'type' => 'permissions',
                        'id' => (string) $p->getKey(),
                    ])->all(),
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/roles/{$role->getKey()}");

        $response->assertSuccessful();

        foreach ($newPermissions as $permission) {
            $this->assertDatabaseHas('role_has_permissions', [
                'role_id' => $role->getKey(),
                'permission_id' => $permission->getKey(),
            ]);
        }

        foreach ($initial as $permission) {
            $this->assertDatabaseMissing('role_has_permissions', [
                'role_id' => $role->getKey(),
                'permission_id' => $permission->getKey(),
            ]);
        }
    }

    public function test_user_without_permission_cannot_update_role_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['guard_name' => 'api']);
        $permission = Permission::first();

        $payload = [
            'type' => 'roles',
            'id' => (string) $role->getKey(),
            'attributes' => [
                'name' => 'unauthorized.role',
                'guard_name' => 'api',
            ],
            'relationships' => [
                'permissions' => [
                    'data' => [
                        ['type' => 'permissions', 'id' => (string) $permission->getKey()],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/roles/{$role->getKey()}");

        $response->assertForbidden();
    }
}
