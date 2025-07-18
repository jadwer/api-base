<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\User\Models\User;
use Tests\TestCase;

class F2RoleStoreWithPermissionsTest extends TestCase
{
    public function test_god_can_create_role_with_permissions(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $permissions = Permission::query()->take(2)->get();

        $payload = [
            'type' => 'roles',
            'attributes' => [
                'name' => 'role.with.permissions',
                'guard_name' => 'api',
            ],
            'relationships' => [
                'permissions' => [
                    'data' => $permissions->map(fn($p) => [
                        'type' => 'permissions',
                        'id' => (string) $p->getKey(),
                    ])->all(),
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/roles');

        $response->assertCreated();
        $this->assertDatabaseHas('roles', ['name' => 'role.with.permissions']);

        $roleId = (int) $response->json('data.id');
        foreach ($permissions as $permission) {
            $this->assertDatabaseHas('role_has_permissions', [
                'role_id' => $roleId,
                'permission_id' => $permission->getKey(),
            ]);
        }
    }

    public function test_jsonapi_format_error_returns_400(): void
    {
        $user = User::where('email', 'god@example.com')->first();

        $payload = [
            'type' => 'roles',
            'attributes' => [
                'name' => 'broken.relationship.format',
                'guard_name' => 'api',
            ],
            'relationships' => [
                'permissions' => [
                    'data' => [
                        ['type' => 'permissions', 'id' => null],
                        ['type' => 'invalid', 'id' => '999'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/roles');

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'detail' => 'The member id must be a string.',
        ]);
    }

    public function test_validation_error_returns_422(): void
    {
        $user = User::where('email', 'god@example.com')->first();

        $payload = [
            'type' => 'roles',
            'attributes' => (object) [
                // intentionally omit name and guard_name
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/roles');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
            '/data/attributes/guard_name',
        ], $response);
    }

    public function test_user_without_permission_cannot_create_role_with_permissions(): void
    {
        $user = User::factory()->create();
        $permission = Permission::query()->first();

        $payload = [
            'type' => 'roles',
            'attributes' => [
                'name' => 'forbidden.role',
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
            ->post('/api/v1/roles');

        $response->assertForbidden();
    }
}
