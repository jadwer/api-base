<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\User\Models\User;
use Tests\TestCase;

class F1PermissionShowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

public function test_god_can_view_permission(): void
{
    $user = User::where('email', 'god@example.com')->first();
    $permission = Permission::first();

    $response = $this->actingAs($user, 'sanctum')
        ->jsonApi()
        ->get("/api/v1/permissions/{$permission->getRouteKey()}");

    $response->assertSuccessful();
    $response->assertFetchedOne([
        'type' => 'permissions',
        'id' => (string) $permission->id,
        'attributes' => [
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ],
    ]);
}

    public function test_user_without_permission_cannot_view_permission(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->jsonApi()->get("api/v1/permissions/{$permission->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_permission(): void
    {
        $permission = Permission::factory()->create();

        $response = $this->jsonApi()->get("api/v1/permissions/{$permission->id}");

        $response->assertUnauthorized();
    }
}
