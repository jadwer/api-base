<?php

namespace Modules\Auth\Tests\Feature;

use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Hardening del registro publico (decision 2026-09-08, pre Stripe LIVE):
 * la cuenta nace inactive y sin token, el correo verificado la activa,
 * y el honeypot descarta bots en silencio.
 */
class RegisterTest extends TestCase
{
    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cliente Nuevo',
            'email' => 'nuevo@example.com',
            'password' => 'SecretaSegura1!',
        ], $overrides);
    }

    public function test_el_registro_nace_inactive_sin_token_y_con_rol_customer(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload());

        $response->assertCreated();
        $response->assertJsonMissingPath('access_token');

        $user = User::where('email', 'nuevo@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('inactive', $user->status);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertNull($user->email_verified_at);
    }

    public function test_no_puede_iniciar_sesion_antes_de_verificar_el_correo(): void
    {
        $this->postJson('/api/auth/register', $this->registerPayload());

        $response = $this->postJson('/api/auth/login', [
            'email' => 'nuevo@example.com',
            'password' => 'SecretaSegura1!',
        ]);

        $response->assertUnauthorized();
    }

    public function test_verificar_el_correo_activa_la_cuenta_y_habilita_el_login(): void
    {
        $this->postJson('/api/auth/register', $this->registerPayload());
        $user = User::where('email', 'nuevo@example.com')->first();

        $hash = sha1($user->getEmailForVerification());
        $this->getJson("/api/auth/email/verify/{$user->id}/{$hash}")->assertOk();

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertNotNull($user->email_verified_at);

        $this->postJson('/api/auth/login', [
            'email' => 'nuevo@example.com',
            'password' => 'SecretaSegura1!',
        ])->assertOk();
    }

    public function test_verificar_no_reactiva_una_cuenta_bloqueada(): void
    {
        $this->postJson('/api/auth/register', $this->registerPayload());
        $user = User::where('email', 'nuevo@example.com')->first();
        $user->forceFill(['status' => 'banned'])->save();

        $hash = sha1($user->getEmailForVerification());
        $this->getJson("/api/auth/email/verify/{$user->id}/{$hash}")->assertOk();

        $this->assertSame('banned', $user->fresh()->status);
    }

    public function test_honeypot_lleno_responde_exito_generico_sin_crear_usuario(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload([
            'website' => 'https://spam.example.com',
        ]));

        $response->assertCreated();
        $this->assertNull(User::where('email', 'nuevo@example.com')->first());
    }

    public function test_la_ruta_de_registro_conserva_su_throttle(): void
    {
        $route = collect(app('router')->getRoutes())->first(
            fn ($r) => $r->getName() === 'api.auth.register'
        );

        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->gatherMiddleware());
    }
}
