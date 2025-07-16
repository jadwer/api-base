<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class F1RoleIndexTest extends TestCase
{
    public function test_god_can_list_roles(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $roles = Role::all();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('roles')
            ->get('/api/v1/roles');

        $response->assertSuccessful();
        $response->assertFetchedMany($roles);
    }

    public function test_user_without_permission_cannot_list_roles(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->jsonApi()->get('/api/v1/roles');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_roles(): void
    {
        $response = $this->jsonApi()->get('/api/v1/roles');

        $response->assertUnauthorized();
    }
}