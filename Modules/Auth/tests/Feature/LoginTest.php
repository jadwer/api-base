<?php

namespace Modules\Auth\Tests\Feature;

use Modules\User\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Recuperar el usuario admin desde la base de datos (debe haber sido creado por el seeder)
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        // Intentar hacer login con las credenciales correctas del usuario admin
        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'admin@example.com',
            'password' => 'secureadmin', // Contraseña que definimos en el seeder
        ]);

        // Verificar que la respuesta es exitosa y contiene el token
        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'email', 'name', 'status'],
            ]);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        // Recuperar el usuario admin desde la base de datos (debe haber sido creado por el seeder)
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        // Verificar que la respuesta es un error 401 (no autorizado)
        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Credenciales inválidas.']);
    }

    public function test_user_cannot_login_with_nonexistent_email(): void
    {
        // Intentar hacer login con un correo no registrado
        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'irrelevant',
        ]);

        // Verificar que la respuesta es un error 422 (unprocessable entity)
        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'El correo no está registrado.']);
    }

    public function test_login_fails_with_missing_fields(): void
    {
        // Intentar hacer login sin proporcionar ningún campo
        $response = $this->postJson(route('api.auth.login'), []);

        // Verificar que la respuesta es un error 422 con los campos faltantes
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_fails_with_invalid_type_field(): void
    {
        // Intentar hacer login con un campo 'type' no esperado
        $response = $this->postJson(route('api.auth.login'), [
            'type' => 'invalid-type',
            'email' => 'admin@example.com',
            'password' => 'secureadmin',
        ]);

        // Verificar que la respuesta es un error 422
        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Campos no permitidos: type']);
    }

    public function test_user_cannot_login_when_soft_deleted(): void
    {
        // Crear un usuario marcado como soft deleted
        $user = User::factory()->create([
            'email' => 'deleted@example.com',
            'password' => 'secureadmin',
            'status' => 'active',
            'deleted_at' => now(),
        ]);

        // Intentar hacer login con el usuario soft deleted
        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'deleted@example.com',
            'password' => 'secureadmin',
        ]);

        // Verificar que la respuesta es un error 401
        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Credenciales inválidas.']);
    }

    public function test_user_cannot_login_if_status_is_inactive(): void
    {
        // Crear un usuario con el estado 'inactive'
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'secureadmin',
            'status' => 'inactive',
        ]);

        // Intentar hacer login con el usuario inactivo
        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'inactive@example.com',
            'password' => 'secureadmin',
        ]);

        // Verificar que la respuesta es un error 401
        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Credenciales inválidas.']);
    }

public function test_user_can_authenticate_with_token_after_login(): void
{
    // Crear usuario con contraseña hasheada
    $user = User::factory()->create([
        'email' => 'token_admin@example.com',
        'password' => 'secureadmin',
        'status' => 'active',
    ]);
    $user->assignRole('admin');

    // Enviar petición de login
    $loginResponse = $this->postJson(route('api.auth.login'), [
        'email' => 'token_admin@example.com',
        'password' => 'secureadmin',
    ]);

    // Verificar respuesta exitosa y token presente
    $loginResponse->assertOk();
    $token = $loginResponse->json('access_token');
    $this->assertNotEmpty($token, 'Token de acceso no fue retornado');

    // Usar el token para acceder al recurso protegido (users.show)
    $authenticatedResponse = $this
        ->withHeader('Authorization', 'Bearer ' . $token)
        ->jsonApi()
        ->get("/api/v1/users/{$user->id}");

    // Validar que accede correctamente y obtiene el email en el JSON:API
    $authenticatedResponse->assertOk()
        ->assertJsonFragment([
            'type' => 'users',
            'id'   => (string) $user->id,
        ])
        ->assertJsonPath('data.attributes.email', 'token_admin@example.com');
}

}
