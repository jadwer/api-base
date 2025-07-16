<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserIndexTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/users');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'status',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
            'jsonapi',
        ]);
    }

    public function test_admin_can_sort_users_by_name(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/users?sort=name');

        $response->assertOk();
        $this->assertIsArray($response->json('data'));

        $names = array_column($response->json('data'), 'attributes.name');
        $sorted = $names;
        sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);

        $this->assertEquals($sorted, $names);
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        $response = $this->jsonApi()->get('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_tech_can_list_users_but_not_sensitive_fields(): void
    {
        $tech = User::where('email', 'tech@example.com')->firstOrFail();
        $this->actingAs($tech, 'sanctum');

        $response = $this->jsonApi()->get('/api/v1/users');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'status',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ],
        ]);
    }
}
