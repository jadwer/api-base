<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class F1RoleUpdateTest extends TestCase
{
    public function test_god_can_update_role(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $role = Role::factory()->create();

        $payload = [
            'type' => 'roles',
            'id' => (string) $role->id,
            'attributes' => [
                'name' => 'updated.name',
                'guard_name' => 'web',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/roles/{$role->id}");

        $response->assertSuccessful();
        $this->assertDatabaseHas('roles', ['name' => 'updated.name']);
    }

    public function test_user_without_permission_cannot_update_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $payload = [
            'type' => 'roles',
            'id' => (string) $role->id,
            'attributes' => [
                'name' => 'unauthorized.update',
                'guard_name' => 'web',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/roles/{$role->id}");

        $response->assertForbidden();
    }

  public function test_validation_errors_when_updating_role(): void
{
    $user = User::where('email', 'god@example.com')->first();
    $role = Role::factory()->create();

    $payload = [
        'type' => 'roles',
        'id' => (string) $role->id,
        'attributes' => (object) [
            'name' => null,
            'guard_name' => '',
        ],
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->jsonApi()
        ->withData($payload)
        ->patch("/api/v1/roles/{$role->id}");

    $response->assertStatus(422);

    $this->assertJsonApiValidationErrors(['/data/attributes/name', '/data/attributes/guard_name'], $response);
}

}