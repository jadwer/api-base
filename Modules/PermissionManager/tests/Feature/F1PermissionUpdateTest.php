<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\User\Models\User;
use Tests\TestCase;

class F1PermissionUpdateTest extends TestCase
{
    public function test_god_can_update_permission(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $permission = Permission::factory()->create(['name' => 'old.name']);

        $payload = [
            'type' => 'permissions',
            'id' => (string) $permission->id,
            'attributes' => [
                'name' => 'updated.name',
                'guard_name' => 'api',
            ],
        ];

        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/permissions/{$permission->id}");

        $response->assertSuccessful();
        $this->assertDatabaseHas('permissions', ['name' => 'updated.name']);
    }

    public function test_user_without_permission_cannot_update_permission(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $payload = [
            'type' => 'permissions',
            'id' => (string) $permission->id,
            'attributes' => [
                'name' => 'not.allowed',
                'guard_name' => 'api',
            ],
        ];

        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/permissions/{$permission->id}");

        $response->assertForbidden();
    }

public function test_validation_errors_when_updating_permission(): void
{
    $user = User::where('email', 'god@example.com')->first();
    $permission = Permission::factory()->create();

    $payload = [
        'type' => 'permissions',
        'id' => (string) $permission->id,
        'attributes' => [
            'name' => null, // <--- inválido
        ],
    ];

    $response = $this
        ->actingAs($user, 'sanctum')
        ->jsonApi()
        ->withData($payload)
        ->patch("/api/v1/permissions/{$permission->id}");

    $response->assertStatus(422);
    $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
}

}
