<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:seed', ['module' => 'User']);
    }

    public function test_authenticated_user_can_update_user(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $targetUser = User::factory()->create()->assignRole('customer');

        $payload = [
            'type' => 'users',
            'id' => (string) $targetUser->id,
            'attributes' => [
                'name' => 'Nuevo Nombre',
                'email' => 'nuevo@example.com',
                'status' => 'active',
                'role' => 'customer',
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Nuevo Nombre',
            'email' => 'nuevo@example.com',
            'status' => 'active',
        ]);

        $this->assertTrue(
            $targetUser->fresh()->hasRole('customer'),
            'El usuario no tiene el rol esperado.'
        );
    }

    public function test_unauthenticated_user_cannot_update_user(): void
    {
        $targetUser = User::factory()->create();

        $payload = [
            'type' => 'users',
            'id' => (string) $targetUser->id,
            'attributes' => [
                'name' => 'Cambio No Autorizado',
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(401);
    }

    public function test_update_fails_with_missing_required_fields(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $targetUser = User::factory()->create();

        $payload = [
            'type' => 'users',
            'id' => (string) $targetUser->id,
            'attributes' => [
                'name' => null,
                'email' => null,
                'role' => null,
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
            '/data/attributes/email',
            '/data/attributes/role',
        ], $response);
    }

    public function test_update_fails_with_duplicate_email(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $existingUser = User::factory()->create(['email' => 'existente@example.com']);
        $targetUser = User::factory()->create()->assignRole('customer');

        $payload = [
            'type' => 'users',
            'id' => (string) $targetUser->id,
            'attributes' => [
                'name' => 'Nombre actualizado',
                'email' => $existingUser->email,
                'status' => 'active',
                'role' => 'customer',
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/email'], $response);
    }

    public function test_update_fails_with_invalid_type(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $targetUser = User::factory()->create();

        $payload = [
            'type' => 'not-users',
            'id' => (string) $targetUser->id,
            'attributes' => [
                'name' => 'Cambio',
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(409);
    }

    public function test_update_fails_with_unsupported_fields(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $targetUser = User::factory()->create();

        $payload = [
            'type' => 'users',
            'id' => (string) $targetUser->id,
            'attributes' => [
                'name' => 'Nombre',
                'status' => 'active',
                'role' => 'customer',
                'foo' => 'bar', // campo no permitido
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'title' => 'Non-Compliant JSON:API Document',
            'detail' => 'The field foo is not a supported attribute.',
        ]);
    }
}
