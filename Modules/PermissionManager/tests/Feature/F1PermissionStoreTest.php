<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\User\Models\User;
use Tests\TestCase;

class F1PermissionStoreTest extends TestCase
{
    public function test_god_can_create_permission(): void
    {
        $user = User::where('email', 'god@example.com')->first();

        $payload = [
            'type' => 'permissions',
            'attributes' => [
                'name' => 'custom.create',
                'guard_name' => 'api',
            ],
        ];

        $this->jsonApi()->expects('permissions');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/permissions');

        $response->assertCreated();
        $this->assertDatabaseHas('permissions', ['name' => 'custom.create']);
    }

    public function test_user_without_permission_cannot_create_permission(): void
    {
        $user = User::factory()->create();

        $payload = [
            'type' => 'permissions',
            'attributes' => [
                'name' => 'forbidden.create',
                'guard_name' => 'api',
            ],
        ];

        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/permissions');

        $response->assertForbidden();
    }

    public function test_validation_errors_when_creating_permission(): void
    {
        $user = User::where('email', 'god@example.com')->first();

        $payload = [
            'type' => 'permissions',
            'attributes' => (object) [], // 👈 importante: debe ser objeto, no array
        ];

        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->withData($payload)
            ->post('/api/v1/permissions');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
            '/data/attributes/guard_name',
        ], $response);
    }
}
