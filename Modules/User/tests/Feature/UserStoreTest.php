<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:seed', ['module' => 'User']);
    }

    public function test_authenticated_user_can_create_user(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $payload = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'name' => 'Nuevo Usuario',
                    'email' => 'nuevo@example.com',
                    'status' => 'active',
                    'password' => 'secret123',
                    'password_confirmation' => 'secret123'
                ]
            ]
        ];


        $response = $this->postJson('/api/v1/users', $payload, [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ]);


        $response->assertCreated();

        $response->assertJsonStructure([
            'data' => [
                'id', 'type', 'attributes' => [
                    'name', 'email', 'status', 'createdAt', 'updatedAt'
                ]
            ]
        ]);
    }

    public function test_unauthenticated_user_cannot_create_user(): void
    {
        $payload = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'name' => 'Nuevo Usuario',
                    'email' => 'nuevo@example.com',
                    'status' => 'active',
                    'password' => 'secret123',
                    'password_confirmation' => 'secret123'
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/users', $payload, [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ]);

        $response->assertUnauthorized();
    }
}
