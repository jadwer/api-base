<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/** PLA-11 (2026-11-22): los tokens de sesion caducan a los 30 dias. */
class SesionCaduca20261122Test extends TestCase
{
    public function test_un_token_de_mas_de_30_dias_ya_no_autentica_y_uno_reciente_si(): void
    {
        $this->assertSame(43200, config('sanctum.expiration'));
        $user = $this->getAdminUser();
        $token = $user->createToken('auth_token');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)->getJson('/api/v1/quotes/summary')->assertOk();

        $token->accessToken->forceFill(['created_at' => now()->subDays(31)])->save();
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)->getJson('/api/v1/quotes/summary')->assertUnauthorized();
    }

    public function test_la_poda_y_la_limpieza_de_bitacora_estan_programadas(): void
    {
        $commands = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->map(fn ($e) => $e->command)->implode("\n");

        $this->assertStringContainsString('sanctum:prune-expired --hours=24', $commands);
        $this->assertStringContainsString('activitylog:clean datasheet_download --days=90', $commands);
        $this->assertStringContainsString('activitylog:clean --days=365', $commands);
    }
}
