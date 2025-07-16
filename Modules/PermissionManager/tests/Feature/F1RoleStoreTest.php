<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class F1RoleStoreTest extends TestCase
{
    public function test_god_can_create_role(): void
    {
        $user = User::where('email', 'god@example.com')->first();

        $payload = [
            'type' => 'roles',
            'attributes' => [
                'name' => 'new.role',
                'guard_name' => 'web',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/roles');

        $response->assertCreated();
        $this->assertDatabaseHas('roles', ['name' => 'new.role']);
    }

    public function test_user_without_permission_cannot_create_role(): void
    {
        $user = User::factory()->create();

        $payload = [
            'type' => 'roles',
            'attributes' => [
                'name' => 'invalid.role',
                'guard_name' => 'web',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/roles');

        $response->assertForbidden();
    }

    public function test_validation_errors_when_creating_role(): void
    {
        $user = User::where('email', 'god@example.com')->first();

        $payload = [
            'type' => 'roles',
            'attributes' => (object) [],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/roles');

    $response->assertStatus(422);

    $this->assertJsonApiValidationErrors(['/data/attributes/name', '/data/attributes/guard_name'], $response);
    }
}