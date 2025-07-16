<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserStoreTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $data = [
            'type' => 'users',
            'attributes' => [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.com',
                'password' => 'password',
                'status' => 'active',
                'role' => 'customer'
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/users');

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.com',
        ]);
    }

    public function test_god_can_create_user(): void
    {
        $god = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($god, 'sanctum');

        $data = [
            'type' => 'users',
            'attributes' => [
                'name' => 'Usuario God',
                'email' => 'goduser@example.com',
                'password' => 'password',
                'status' => 'active',
                'role' => 'customer'
            ],
        ];

        $response = $this->jsonApi()
            ->withData($data)
            ->post('/api/v1/users');

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'goduser@example.com',
        ]);
    }

    public function test_tech_cannot_create_user(): void
    {
        $tech = User::where('email', 'tech@example.com')->firstOrFail();
        $this->actingAs($tech, 'sanctum');

        $response = $this->jsonApi()
            ->withData([
                'type' => 'users',
                'attributes' => [
                    'name' => 'Usuario Tech',
                    'email' => 'techuser@example.com',
                    'password' => 'password',
                    'status' => 'active',
                    'role' => 'customer'
                ],
            ])
            ->post('/api/v1/users');

        $response->assertForbidden();
        $response->assertSeeText('No tienes permiso para crear usuarios.');
    }

    public function test_unauthenticated_user_cannot_create_user(): void
    {
        $response = $this->jsonApi()
            ->withData([
                'type' => 'users',
                'attributes' => [
                    'name' => 'Usuario Sin Auth',
                    'email' => 'sin-auth@example.com',
                    'password' => 'password',
                    'status' => 'active',
                    'role' => 'customer'
                ],
            ])
            ->post('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_user_creation_fails_with_missing_fields(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()
            ->withData([
                'type' => 'users',
                'attributes' => new \stdClass(), // evita el error de tipo 400
            ])
            ->post('/api/v1/users');

        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
            '/data/attributes/email',
            '/data/attributes/password',
            '/data/attributes/status',
        ], $response);
    }

    public function test_user_creation_fails_with_duplicate_email(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $existing = User::factory()->create(['email' => 'duplicado@example.com']);

        $response = $this->jsonApi()
            ->withData([
                'type' => 'users',
                'attributes' => [
                    'name' => 'Duplicado',
                    'email' => 'duplicado@example.com',
                    'password' => 'password',
                    'status' => 'active',
                    'role' => 'customer'
                ],
            ])
            ->post('/api/v1/users');

        $this->assertJsonApiValidationErrors([
            '/data/attributes/email',
        ], $response);
    }

    public function test_user_creation_fails_with_invalid_type(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'sanctum');

        $response = $this->jsonApi()
            ->withData([
                'type' => 'invalid-type',
                'attributes' => [
                    'name' => 'Usuario X',
                    'email' => 'x@example.com',
                    'password' => 'password',
                    'status' => 'active',
                    'role' => 'customer'
                ],
            ])
            ->post('/api/v1/users');

        $response->assertStatus(409); // JSON:API Conflict por tipo incorrecto
    }
}
