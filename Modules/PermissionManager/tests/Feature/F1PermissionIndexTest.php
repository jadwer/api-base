<?php

namespace Modules\PermissionManager\Tests\Feature;

use Modules\PermissionManager\Models\Permission;
use Modules\User\Models\User;
use Tests\TestCase;

class F1PermissionIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_god_can_list_permissions(): void
    {
        $user = User::where('email', 'god@example.com')->first();
        $permissions = Permission::all();


        $response = $this
            ->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('permissions')
            ->get('/api/v1/permissions');

        $response->assertSuccessful();
        $response->assertFetchedMany($permissions);
    }

    public function test_user_without_permission_cannot_list_permissions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->jsonApi()->get('api/v1/permissions');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_permissions(): void
    {
        $response = $this->jsonApi()->get('api/v1/permissions');

        $response->assertUnauthorized();
    }
}
