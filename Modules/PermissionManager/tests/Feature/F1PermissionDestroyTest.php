<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\User\Models\User;
use Tests\TestCase;

class F1PermissionDestroyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_god_can_delete_permission(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $permission = Permission::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/permissions/{$permission->getRouteKey()}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_user_without_permission_cannot_delete_permission(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->delete("/api/v1/permissions/{$permission->getRouteKey()}");

        $response->assertForbidden();
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
    }

    public function test_unauthenticated_user_cannot_delete_permission(): void
    {
        $permission = Permission::factory()->create();

        $response = $this->jsonApi()->delete("/api/v1/permissions/{$permission->getRouteKey()}");

        $response->assertUnauthorized();
    }
}
