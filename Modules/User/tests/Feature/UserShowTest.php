<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:seed', ['module' => 'User']);
    }

    public function test_authenticated_user_can_view_another_user(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $targetUser = User::factory()->create([
            'name' => 'Gabino Ramírez',
            'email' => 'gabino@example.com',
            'status' => 'active',
        ]);

        $response = $this->jsonApi()->get("/api/v1/users/{$targetUser->id}");

        $response->assertOk()->assertJson([
            'data' => [
                'id' => (string) $targetUser->id,
                'type' => 'users',
                'attributes' => [
                    'name' => 'Gabino Ramírez',
                    'email' => 'gabino@example.com',
                    'status' => 'active',
                ]
            ]
        ]);
    }

    public function test_unauthenticated_user_cannot_view_user(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->jsonApi()->get("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(401);
    }
}
