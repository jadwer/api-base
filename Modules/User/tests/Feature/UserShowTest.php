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

        // Seed completo del módulo
        $this->artisan('module:seed', ['module' => 'User']);
    }

    public function test_authenticated_user_can_view_another_user(): void
    {
        // Recupera el usuario 'god'
        $authUser = User::where('email', 'god@example.com')->firstOrFail();

        // Autenticación simulada
        $this->actingAs($authUser, 'sanctum');

        // Crear usuario objetivo
        $targetUser = User::factory()->create([
            'name' => 'Gabino Ramírez',
            'email' => 'gabino@example.com',
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/v1/users/{$targetUser->id}",
            ['Accept' => 'application/vnd.api+json']
        );


        $response->assertOk();
        $response->assertJson([
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

        $response = $this->getJson(
            "/api/v1/users/{$targetUser->id}",
            ['Accept' => 'application/vnd.api+json']
        );

        $response->assertUnauthorized();
    }
}
