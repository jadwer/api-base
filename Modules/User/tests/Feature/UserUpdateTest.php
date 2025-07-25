<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\PermissionManager\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserUpdateTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_authenticated_user_can_update_user(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $targetUser = User::factory()->create()->assignRole('customer');
        
        // Obtener el role ID del rol 'admin'
        $adminRole = \Modules\PermissionManager\Models\Role::where('name', 'admin')->first();

        $payload = [
            'type' => 'users',
            'id' => (string) $targetUser->id,
            'attributes' => [
                'name' => 'Nuevo Nombre',
                'email' => 'nuevo@example.com',
                'status' => 'active',
            ],
            'relationships' => [
                'roles' => [
                    'data' => [
                        [
                            'type' => 'roles',
                            'id' => (string) $adminRole->id
                        ]
                    ]
                ]
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
            $targetUser->fresh()->hasRole('admin'),
            'El usuario no tiene el rol esperado.'
        );
        
        // Verificar que la respuesta incluye el rol actualizado
        $response->assertJsonPath('data.attributes.role', 'admin');
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
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors([
            '/data/attributes/name',
            '/data/attributes/email',
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

    public function test_can_update_user_roles_relationship(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $targetUser = User::factory()->create()->assignRole('customer');
        
        // Obtener roles para el test
        $godRole = \Modules\PermissionManager\Models\Role::where('name', 'god')->first();
        $adminRole = \Modules\PermissionManager\Models\Role::where('name', 'admin')->first();

        // Asignar múltiples roles
        $payload = [
            'type' => 'users',
            'id' => (string) $targetUser->id,
            'relationships' => [
                'roles' => [
                    'data' => [
                        [
                            'type' => 'roles',
                            'id' => (string) $godRole->id
                        ],
                        [
                            'type' => 'roles',
                            'id' => (string) $adminRole->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200);

        $updatedUser = $targetUser->fresh();
        $this->assertTrue($updatedUser->hasRole('god'));
        $this->assertTrue($updatedUser->hasRole('admin'));
        $this->assertFalse($updatedUser->hasRole('customer'));
        
        // Verificar que el primer rol aparece en el campo 'role'
        $response->assertJsonPath('data.attributes.role', 'god');
    }

    public function test_can_remove_all_roles(): void
    {
        $authUser = User::where('email', 'god@example.com')->firstOrFail();
        $this->actingAs($authUser, 'sanctum');

        $targetUser = User::factory()->create()->assignRole('customer');

        // Remover todos los roles enviando array vacío
        $payload = [
            'type' => 'users',
            'id' => (string) $targetUser->id,
            'relationships' => [
                'roles' => [
                    'data' => []
                ]
            ]
        ];

        $response = $this->jsonApi()
            ->withData($payload)
            ->patch("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200);

        $updatedUser = $targetUser->fresh();
        $this->assertEquals(0, $updatedUser->roles()->count());
        $this->assertNull($updatedUser->getRoleNames()->first());
        
        // Verificar que el campo 'role' es null cuando no hay roles
        $response->assertJsonPath('data.attributes.role', null);
    }
}
