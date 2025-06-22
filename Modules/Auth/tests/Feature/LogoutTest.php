<?php

namespace Modules\Auth\Tests\Feature;

use Modules\User\Models\User;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class LogoutTest extends TestCase
{
    public function test_user_can_logout_successfully(): void
    {
        // Crear un usuario autenticado
        $user = User::where('email', 'admin@example.com')->firstOrFail();
        Sanctum::actingAs($user);

        // Intentar hacer logout
        $response = $this->postJson(route('api.auth.logout'));

        // Verificar que la respuesta es exitosa
        $response->assertOk()
            ->assertJsonFragment(['message' => 'Sesión cerrada correctamente.']);

        // Verificar que el token ha sido eliminado
        $this->assertCount(0, $user->tokens);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        // Intentar hacer logout sin estar autenticado
        $response = $this->postJson(route('api.auth.logout'));

        // Verificar que la respuesta es un error 401 (no autorizado)
        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Unauthenticated.']);
    }

public function test_user_tokens_are_revoked_after_logout(): void
{
    // Crear un usuario
    $user = User::where('email', 'admin@example.com')->firstOrFail();

    // Crear tokens reales
    $token1 = $user->createToken('token-1');
    $token2 = $user->createToken('token-2');

    // Verificar que hay 2 tokens
    $this->assertCount(2, $user->tokens);

    // Usar el primer token para autenticarse y hacer logout
    $headers = ['Authorization' => 'Bearer ' . $token1->plainTextToken];
    $response = $this->postJson(route('api.auth.logout'), [], $headers);

    // Verificar que la respuesta es exitosa
    $response->assertOk();

    // Verificar que solo queda el segundo token
    $tokens = $user->fresh()->tokens;
    $this->assertCount(1, $tokens);
    $this->assertEquals($token2->accessToken->id, $tokens->first()->id);
}

public function test_logout_with_invalid_token(): void
    {
        // Crear un token inválido manualmente
        $headers = ['Authorization' => 'Bearer invalid-token'];

        // Intentar hacer logout con un token inválido
        $response = $this->postJson(route('api.auth.logout'), [], $headers);

        // Verificar que la respuesta es un error 401
        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Unauthenticated.']);
    }
}